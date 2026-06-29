<?php
/**
 * E2E regression for the Customization Extra's integration with the risk form.
 *
 * Exercises the real HTTP path an admin drives: create a custom field on the
 * admin page, see it render as an input on /management/index.php, and have its
 * value persist into custom_risk_data when a risk is submitted carrying it. This
 * is the integration contract curl can see end to end and that has no HTTP
 * coverage today (the integration suite only unit-tests the field engine).
 *
 * The Customization Extra is force-enabled for the run (setting `customization`),
 * snapshotted in setUp and restored in tearDown so the suite leaves no residue.
 * Force-enable via a direct DB write does NOT run the schema installer, so setUp
 * guards on the custom_fields table existing and skips (with a note) if the extra
 * was never activated on this image.
 */
declare(strict_types=1);

final class CustomizationExtraRiskFieldTest extends E2ETestCase
{
    use RiskTestSupportTrait;

    /** @var array<string,?string> snapshot of the customization setting (null = absent). */
    private array $snapshot = [];

    protected function setUp(): void
    {
        parent::setUp();
        // Force-enable does not run customization_install_schema(); the tables must
        // already exist (they do once the extra has ever been activated).
        if (!$this->customFieldsTableExists()) {
            self::markTestSkipped('customization tables absent — the extra was never activated on this image.');
        }
        $this->snapshot = $this->snapshotSettings('customization');
        $this->writeSetting('customization', '1');
    }

    protected function tearDown(): void
    {
        $this->restoreSettings($this->snapshot);
        // tearDownRisks() also calls sweepExtraSeed(), which removes the E2E_ custom
        // fields + their custom_risk_data values created below.
        $this->tearDownRisks();
        parent::tearDown();
    }

    /**
     * Creating a field on the admin page is a PRG 302; the field lands in
     * custom_fields AND renders as name="custom_field_{id}" on the submit form.
     */
    public function test_create_field_persists_and_renders_on_submit_form(): void
    {
        $name = $this->e2eName('CF');

        [$code, , $body] = $this->authedPost('/admin/customization.php', [
            'create_field' => 1,
            'fgroup'       => 'risk',
            'name'         => $name,
            'type'         => 'text',
            'panel_name'   => 'left',
        ]);
        self::assertSame(302, $code, "create_field should PRG-redirect; body: {$body}");

        $fieldId = $this->customFieldIdByName($name);
        self::assertGreaterThan(0, $fieldId, "expected a custom_fields row for '{$name}'");

        [, , $html] = $this->authedGet('/management/index.php');
        self::assertStringContainsString(
            "name='custom_field_{$fieldId}'",
            $html,
            "custom field input custom_field_{$fieldId} did not render on the submit form"
        );
    }

    /**
     * A risk submitted (via the API submit route, which calls submit_risk →
     * save_risk_custom_field_values) carrying custom_field_{id} must persist that
     * value into custom_risk_data(risk_id, field_id, review_id=0, value).
     */
    public function test_custom_field_value_persists_on_submit_risk(): void
    {
        $name = $this->e2eName('CF');
        $this->authedPost('/admin/customization.php', [
            'create_field' => 1, 'fgroup' => 'risk', 'name' => $name, 'type' => 'text', 'panel_name' => 'left',
        ]);
        $fieldId = $this->customFieldIdByName($name);
        self::assertGreaterThan(0, $fieldId, 'custom field was not created');

        $sentinel = 'e2e-custom-value-' . uniqid();
        $publicId = $this->submitRisk($this->uniqueSubject('CF'), [
            "custom_field_{$fieldId}" => $sentinel,
        ]);

        $saved = $this->customFieldValue(self::dbId($publicId), $fieldId);
        self::assertSame($sentinel, $saved, 'custom field value did not persist on submit_risk');
    }

    // ------------------------------------------------------------------
    // Read helpers (DB truth)
    // ------------------------------------------------------------------

    private function customFieldsTableExists(): bool
    {
        try {
            $this->fetchRow('SELECT id FROM custom_fields LIMIT 1');
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function customFieldIdByName(string $name): int
    {
        $row = $this->fetchRow('SELECT id FROM custom_fields WHERE name = ?', [$name]);
        return $row === null ? 0 : (int) $row['id'];
    }

    /** The saved value for a risk (DB id) + field, review_id=0 (current value). Null = none. */
    private function customFieldValue(int $riskDbId, int $fieldId): ?string
    {
        $row = $this->fetchRow(
            'SELECT value FROM custom_risk_data WHERE risk_id = ? AND field_id = ? AND review_id = 0',
            [$riskDbId, $fieldId]
        );
        return $row === null ? null : (string) $row['value'];
    }
}
