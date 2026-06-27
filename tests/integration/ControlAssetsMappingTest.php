<?php
/**
 * Integration test for the batch control->assets mapper added in the
 * header/sidebar refactor (simplerisk/includes/assets.php):
 *   get_control_to_assets_for_controls(array $control_ids) :729
 *
 * It LEFT JOINs control_to_assets / control_to_asset_groups to control_maturity
 * and assets / asset_groups, GROUP_CONCATs names, and MERGES an asset-group row
 * into an existing asset row when they share a control_maturity (the &$existing
 * merge at assets.php:779). Asset names are run through try_decrypt; we pin the
 * encryption global off so plaintext names pass through unchanged.
 *
 * assets.php is NOT in the test bootstrap's functions.php cascade, so we
 * require_once it; db_open() inside the function is pinned to $this->txdb.
 */
declare(strict_types=1);

final class ControlAssetsMappingTest extends IntegrationTestCase
{
    private const CTRL  = 888111; // arbitrary control id (no framework_controls row needed)
    private const MAT_A = 9501;   // control_maturity shared by the asset + group (merge)
    private const MAT_B = 9502;   // control_maturity for a separate group (no merge)

    public static function setUpBeforeClass(): void
    {
        if (!function_exists('get_control_to_assets_for_controls')) {
            require_once SIMPLERISK_APP_ROOT . '/includes/assets.php';
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['db_global'] = $this->txdb;
        // Force try_decrypt passthrough so plaintext asset names come back verbatim
        // (mirrors the customization_extra=false pin in ApiDispatchTest).
        $GLOBALS['encryption_extra'] = false;
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['db_global'], $GLOBALS['encryption_extra']);
        parent::tearDown();
    }

    /** Seed two maturity levels, one asset, two asset groups, and the mappings. */
    private function seed(): array
    {
        $stamp = substr(uniqid('', true), -8);

        $this->txdb->prepare("INSERT INTO `control_maturity` (`value`,`name`) VALUES (?,?),(?,?)")
            ->execute([self::MAT_A, 'Maturity A', self::MAT_B, 'Maturity B']);

        $assetName = 'Asset ' . $stamp;
        $this->txdb->prepare("INSERT INTO `assets` (`name`) VALUES (?)")->execute([$assetName]);
        $assetId = (int)$this->txdb->lastInsertId();

        $groupA = 'GroupA ' . $stamp;
        $groupB = 'GroupB ' . $stamp;
        $this->txdb->prepare("INSERT INTO `asset_groups` (`name`) VALUES (?)")->execute([$groupA]);
        $groupAId = (int)$this->txdb->lastInsertId();
        $this->txdb->prepare("INSERT INTO `asset_groups` (`name`) VALUES (?)")->execute([$groupB]);
        $groupBId = (int)$this->txdb->lastInsertId();

        // Direct asset at MAT_A; asset group at MAT_A (shares maturity -> merges);
        // asset group at MAT_B (different maturity -> separate row).
        $this->txdb->prepare(
            "INSERT INTO `control_to_assets` (`control_id`,`asset_id`,`control_maturity`) VALUES (?,?,?)"
        )->execute([self::CTRL, $assetId, self::MAT_A]);

        $this->txdb->prepare(
            "INSERT INTO `control_to_asset_groups` (`control_id`,`asset_group_id`,`control_maturity`)
             VALUES (?,?,?),(?,?,?)"
        )->execute([
            self::CTRL, $groupAId, self::MAT_A,
            self::CTRL, $groupBId, self::MAT_B,
        ]);

        return [$assetName, $groupA, $groupB];
    }

    public function test_empty_or_garbage_input_returns_empty(): void
    {
        self::assertSame([], get_control_to_assets_for_controls([]));
        self::assertSame([], get_control_to_assets_for_controls([0, 'x', null]));
    }

    public function test_direct_asset_name_is_decrypted_passthrough(): void
    {
        [$assetName] = $this->seed();
        $res = get_control_to_assets_for_controls([self::CTRL]);

        self::assertArrayHasKey(self::CTRL, $res);
        $names = array_filter(array_column($res[self::CTRL], 'asset_name'));
        self::assertContains($assetName, $names);
    }

    public function test_asset_group_merges_into_matching_maturity_row(): void
    {
        [$assetName, $groupA] = $this->seed();
        $res = get_control_to_assets_for_controls([self::CTRL]);

        // Exactly one row at MAT_A, carrying BOTH the direct asset and the merged group.
        $matARows = array_values(array_filter(
            $res[self::CTRL],
            fn ($r) => (int)$r['control_maturity'] === self::MAT_A
        ));
        self::assertCount(1, $matARows, 'asset + group at the same maturity merge into one row');
        self::assertStringContainsString($assetName, (string)$matARows[0]['asset_name']);
        self::assertSame($groupA, $matARows[0]['asset_group_name']);
    }

    public function test_asset_group_at_different_maturity_is_a_separate_row(): void
    {
        [, , $groupB] = $this->seed();
        $res = get_control_to_assets_for_controls([self::CTRL]);

        $matBRows = array_values(array_filter(
            $res[self::CTRL],
            fn ($r) => (int)$r['control_maturity'] === self::MAT_B
        ));
        self::assertCount(1, $matBRows);
        self::assertSame($groupB, $matBRows[0]['asset_group_name']);
        self::assertNull($matBRows[0]['asset_name']); // no direct asset at MAT_B
    }

    public function test_unknown_control_id_returns_no_entry(): void
    {
        $this->seed();
        $res = get_control_to_assets_for_controls([self::CTRL, 777777]);
        self::assertArrayHasKey(self::CTRL, $res);
        self::assertArrayNotHasKey(777777, $res);
    }
}
