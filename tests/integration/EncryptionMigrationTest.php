<?php
/**
 * Integration tests for the Encryption Extra migration helpers
 * (extras/encryption/includes/migration.php): the batch re-encrypt / decrypt-all
 * sweeps and the schema-introspection helpers they depend on.
 *
 * These are the highest-blast-radius code paths in the project — they rewrite
 * risks / assets / frameworks / projects / audit_log / ... in place — so they
 * get real exercise under transaction rollback:
 *
 *   - $GLOBALS['db_global'] is pinned to $this->txdb. migration.php calls
 *     db_open() for every query, and db_open() returns that cached connection
 *     (db_close() is a no-op), so the entire sweep runs inside the rollback
 *     transaction and never touches the live DB.
 *   - $GLOBALS['enc_active_key'] pins a per-test key (enc_fetch_key() reads it
 *     first, before SIMPLERISK_ENCRYPTION_KEY), so the real key is never used.
 *   - The DDL helpers (enc_ensure_column / enc_widen_column) run ALTER TABLE,
 *     which implicitly commits in MySQL and would break rollback / pollute the
 *     dev schema. They are therefore exercised for GUARD LOGIC ONLY — these
 *     tests never add a column and never call enc_ensure_columns().
 *
 * Assertions target the rows each test inserts (read back by id / by marker) so
 * they stay deterministic regardless of whatever data the dev DB already holds.
 */
declare(strict_types=1);

final class EncryptionMigrationTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::loadExtra('encryption', 'crypto.php', 'migration.php');
        // Route every db_open() the sweeps make onto the rollback transaction,
        // and pin a per-test key so the real encryption key is never consulted.
        $GLOBALS['db_global']            = $this->txdb;
        $GLOBALS['enc_active_key']       = uniqid('k', true);
        $GLOBALS['enc_encrypt_failures'] = 0;
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['db_global'], $GLOBALS['enc_active_key']);
        $GLOBALS['enc_encrypt_failures'] = 0;
        parent::tearDown();
    }

    /** Insert a plaintext risk (only the NOT-NULL-no-default columns) and return its id. */
    private function insertRisk(string $subject, string $assessment = '', string $notes = ''): int
    {
        $stmt = $this->txdb->prepare(
            "INSERT INTO risks (status, subject, source, category, owner, manager, assessment, notes)
             VALUES ('New', :subject, 0, 0, 0, 0, :assessment, :notes)"
        );
        $stmt->execute([':subject' => $subject, ':assessment' => $assessment, ':notes' => $notes]);
        return (int)$this->txdb->lastInsertId();
    }

    /** Insert a plaintext audit_log row (the table has NO primary key). */
    private function insertAuditLog(string $message): void
    {
        $stmt = $this->txdb->prepare(
            "INSERT INTO audit_log (risk_id, message, log_type) VALUES (1, :msg, 'risk')"
        );
        $stmt->execute([':msg' => $message]);
    }

    /** Read one column of one risk by id. */
    private function riskColumn(int $id, string $column): ?string
    {
        $stmt = $this->txdb->prepare("SELECT `{$column}` FROM risks WHERE id = :id LIMIT 1");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $v = $stmt->fetchColumn();
        return $v === false ? null : (string)$v;
    }

    public function test_column_inventory_matches_core_encrypt_sites(): void
    {
        // The inventory MUST match exactly the columns core wraps with
        // try_encrypt()/try_decrypt(); drift here silently leaves data
        // unencrypted (or strands ciphertext on disable).
        $inv = enc_column_inventory();
        self::assertSame(['subject', 'assessment', 'notes'], $inv['risks']);
        self::assertSame(['name', 'ip', 'details'], $inv['assets']);
        self::assertSame(['comment'], $inv['comments']);
        self::assertSame(
            ['current_solution', 'security_requirements', 'security_recommendations'],
            $inv['mitigations']
        );
        self::assertSame(['comments'], $inv['mgmt_reviews']);
        self::assertSame(['comment'], $inv['framework_control_test_comments']);
        self::assertSame(['name', 'description'], $inv['frameworks']);
        self::assertSame(['message'], $inv['audit_log']);
        self::assertSame(['name'], $inv['projects']);
    }

    public function test_bulk_reencrypt_encrypts_plaintext_columns(): void
    {
        $key = $GLOBALS['enc_active_key'];
        $id = $this->insertRisk('Secret Subject', 'Secret Assessment', 'Secret Notes');

        $res = enc_bulk_reencrypt(100);
        self::assertTrue($res['done']);
        self::assertGreaterThan(0, $res['updated'], 'at least the inserted columns were encrypted');

        $expect = ['subject' => 'Secret Subject', 'assessment' => 'Secret Assessment', 'notes' => 'Secret Notes'];
        foreach ($expect as $col => $plaintext) {
            $val = $this->riskColumn($id, $col);
            self::assertNotNull($val);
            self::assertStringStartsWith('ENC1:', $val, $col);
            self::assertSame($plaintext, enc_decrypt($key, $val), $col);
        }
    }

    public function test_bulk_reencrypt_is_idempotent_on_encrypted_rows(): void
    {
        $key = $GLOBALS['enc_active_key'];
        $id = $this->insertRisk('Stable Subject');
        enc_bulk_reencrypt(100);
        $afterFirst = $this->riskColumn($id, 'subject');
        self::assertStringStartsWith('ENC1:', $afterFirst);

        // A second sweep skips already-ENC1: rows (NOT LIKE 'ENC1:%'); the value
        // is neither re-wrapped nor altered.
        $res = enc_bulk_reencrypt(100);
        self::assertTrue($res['done']);

        $afterSecond = $this->riskColumn($id, 'subject');
        self::assertSame($afterFirst, $afterSecond, 'an encrypted value must not be re-wrapped');
        self::assertSame('Stable Subject', enc_decrypt($key, $afterSecond));
    }

    public function test_bulk_reencrypt_handles_no_pk_table_via_distinct_values(): void
    {
        // audit_log has no primary key, so the sweep re-encrypts by matching the
        // DISTINCT plaintext value (the same plaintext recurs across rows).
        $key = $GLOBALS['enc_active_key'];
        $recurs = 'unittest-audit-' . uniqid();
        $single = $recurs . '-only';
        $this->insertAuditLog($recurs);
        $this->insertAuditLog($recurs); // same plaintext, two rows
        $this->insertAuditLog($single);

        $res = enc_bulk_reencrypt(100);
        self::assertTrue($res['done']);

        // Count rows whose decrypt-under-test-key yields each plaintext: the
        // recurring value hit both rows, the unique value one. Pre-existing dev
        // rows (encrypted with another key, or unrelated plaintext) never match.
        $rows = $this->txdb->query("SELECT message FROM audit_log WHERE risk_id = 1 AND log_type = 'risk'")
            ->fetchAll(PDO::FETCH_COLUMN);
        $hits = $only = 0;
        foreach ($rows as $m) {
            $pt = enc_decrypt($key, (string)$m);
            if ($pt === $recurs) { $hits++; }
            elseif ($pt === $single) { $only++; }
        }
        self::assertSame(2, $hits, 'the recurring plaintext was encrypted in every row it appears in');
        self::assertSame(1, $only, 'the unique plaintext was encrypted');
    }

    public function test_bulk_reencrypt_advances_pk_cursor_across_batches(): void
    {
        // batch=1 forces the forward-only PK cursor (id > :last) to iterate more
        // than one page. The test returning proves the cursor advances past each
        // encrypted row instead of re-selecting it forever.
        $key = $GLOBALS['enc_active_key'];
        $ids = [];
        for ($i = 0; $i < 3; $i++) {
            $ids[] = $this->insertRisk('Cursor Page ' . $i);
        }

        $res = enc_bulk_reencrypt(1);
        self::assertTrue($res['done']);

        foreach ($ids as $i => $id) {
            $v = $this->riskColumn($id, 'subject');
            self::assertStringStartsWith('ENC1:', $v, "row {$i}");
            self::assertSame('Cursor Page ' . $i, enc_decrypt($key, $v), "row {$i}");
        }
    }

    public function test_bulk_decrypt_all_restores_plaintext(): void
    {
        $key = $GLOBALS['enc_active_key'];
        $id = $this->insertRisk('Reversible Subject', 'Reversible Assessment');
        enc_bulk_reencrypt(100);
        self::assertStringStartsWith('ENC1:', $this->riskColumn($id, 'subject'));

        $count = enc_bulk_decrypt_all();
        self::assertGreaterThan(0, $count, 'at least the inserted rows were decrypted');

        $subject = $this->riskColumn($id, 'subject');
        self::assertStringStartsNotWith('ENC1:', $subject, 'value is plaintext again');
        self::assertSame('Reversible Subject', enc_decrypt($key, $subject));
    }

    public function test_int_primary_key_detection(): void
    {
        self::assertSame('id', enc_int_primary_key('risks'), 'risks has a single int PK');
        self::assertNull(enc_int_primary_key('audit_log'), 'audit_log has no PK -> distinct-value path');
    }

    public function test_list_tables_and_encryptable_columns(): void
    {
        self::assertContains('risks', enc_list_tables());
        $cols = enc_encryptable_columns('risks');
        self::assertContains('subject', $cols);
        self::assertContains('assessment', $cols);
        self::assertNotContains('id', $cols, 'numeric columns can never carry ciphertext');
    }

    public function test_column_exists_guard_without_mutating(): void
    {
        // Guard logic only — never trigger an ALTER (which would implicitly commit).
        self::assertTrue(enc_column_exists('risks', 'subject'));
        self::assertFalse(enc_column_exists('risks', 'definitely_not_a_column_xyz'));
        // enc_widen_column on an already-LONGTEXT column is a no-op (early return),
        // so calling it here mutates nothing and must not throw.
        enc_widen_column('risks', 'subject');
        self::addToAssertionCount(1);
    }

    public function test_create_subject_order_runs_under_column_guard(): void
    {
        $key = $GLOBALS['enc_active_key'];
        $id = $this->insertRisk('Ordered Subject');
        enc_bulk_reencrypt(100); // make the subject an ENC1: token

        // Runs whether or not the order_by_subject shadow column exists (the
        // guard returns early when absent). When present, the shadow must hold
        // the decrypted plaintext.
        enc_create_subject_order($key);
        if (enc_column_exists('risks', 'order_by_subject')) {
            self::assertSame('Ordered Subject', $this->riskColumn($id, 'order_by_subject'));
        } else {
            self::addToAssertionCount(1); // guard exercised; column absent in this env
        }
    }
}
