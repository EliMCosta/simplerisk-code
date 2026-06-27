<?php
/**
 * Unit tests for the CVSS v2 scoring math in simplerisk/includes/cvss.php.
 *
 * These functions are pure (no DB). The only DB touch in cvss.php is
 * overall_score()'s fallback to get_setting('default_risk_score'), which is only
 * reached for an impossible score type — never hit by these inputs.
 *
 * Expected values are hand-computed from the cvss.php formulas using the metric
 * constants SimpleRisk actually uses (see fixtures/cvss_vectors.php header).
 */
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CvssTest extends TestCase
{
    private const DELTA = 0.0001;

    /**
     * @dataProvider vectors
     */
    public function test_calculate_cvss_score(
        string $label,
        float $av, float $ac, float $au,
        float $c, float $i, float $a,
        $e, $rl, $rc,
        $cdp, $td,
        $cr, $ir, $ar,
        float $expected
    ): void {
        $score = calculate_cvss_score($av, $ac, $au, $c, $i, $a, $e, $rl, $rc, $cdp, $td, $cr, $ir, $ar);

        self::assertEqualsWithDelta(
            $expected,
            $score,
            self::DELTA,
            "CVSS vector '{$label}': expected {$expected}, got {$score}"
        );
    }

    public static function vectors(): array
    {
        return require __DIR__ . '/../fixtures/cvss_vectors.php';
    }

    public function test_round_up_1_decimal_always_rounds_up(): void
    {
        // ceil(value * 10) / 10
        self::assertEqualsWithDelta(7.9, round_up_1_decimal(7.84), self::DELTA);
        self::assertEqualsWithDelta(7.1, round_up_1_decimal(7.01), self::DELTA); // nudges up, never down
        self::assertEqualsWithDelta(7.0, round_up_1_decimal(7.0), self::DELTA);
        self::assertEqualsWithDelta(0.0, round_up_1_decimal(0), self::DELTA);
        self::assertEqualsWithDelta(10.1, round_up_1_decimal(10.01), self::DELTA);
    }

    public function test_exploitability_subscore(): void
    {
        // 20 * AccessComplexity * Authentication * AccessVector
        self::assertEqualsWithDelta(20 * 0.71 * 0.704 * 1.0, exploitability_subscore(0.71, 0.704, 1.0), self::DELTA);
        self::assertEqualsWithDelta(20 * 0.35 * 0.56 * 0.395, exploitability_subscore(0.35, 0.56, 0.395), self::DELTA);
    }

    public function test_impact_formula(): void
    {
        // 10.41 * (1 - (1-C)(1-I)(1-A))
        self::assertEqualsWithDelta(0.0, impact(0.0, 0.0, 0.0), self::DELTA);
        self::assertEqualsWithDelta(10.41 * (1 - (1 - 0.275) ** 3), impact(0.275, 0.275, 0.275), self::DELTA);
        self::assertEqualsWithDelta(10.41 * (1 - 0.34 * 0.34 * 0.34), impact(0.66, 0.66, 0.66), self::DELTA);
    }

    public function test_impact_function_switch(): void
    {
        self::assertSame(0, impact_function(0));
        self::assertEqualsWithDelta(1.176, impact_function(6.4), self::DELTA);
    }

    public function test_temporal_score_scales_base_by_temporal_metrics(): void
    {
        // temporal = base * Exploitability * RemediationLevel * ReportConfidence
        self::assertEqualsWithDelta(8.0 * 0.85 * 0.90 * 0.95, temporal_score(8.0, 0.85, 0.90, 0.95), self::DELTA);
        // All-1 temporal metrics leave the base score unchanged
        self::assertEqualsWithDelta(7.5, temporal_score(7.5, 1.0, 1.0, 1.0), self::DELTA);
    }
}
