<?php
/**
 * CVSS v2 test vectors for calculate_cvss_score.
 *
 * IMPORTANT: SimpleRisk's metric numeric values are NOT the official CVSS v2
 * spec values (e.g. AV:N = 1.0 here vs the official 0.7875 — see
 * simplerisk/management/partials/cvss_modal_content.php:242-261). So the
 * expected scores below are hand-computed from simplerisk/includes/cvss.php
 * using SimpleRisk's constants, NOT NVD scores.
 *
 * Each row (consumed by CvssTest::vectors as a PHPUnit data provider):
 *   [label, AV, AC, Au, C, I, A, E, RL, RC, CDP, TD, CR, IR, AR, expectedOverall]
 * A value of -1 means "not defined" (CVSS ND) — it selects the base/temporal/
 * environmental branch in score_type().
 */
return [
    // All impacts None, base only -> 0.0
    ['base all-none',        1.0, 0.71, 0.704, 0.0,   0.0,   0.0,   -1, -1, -1, -1, -1, -1, -1, -1, 0.0],
    // All impacts Complete, base only -> 10.1
    ['base all-complete',    1.0, 0.71, 0.704, 0.66,  0.66,  0.66,  -1, -1, -1, -1, -1, -1, -1, -1, 10.1],
    // Partial impacts, temporal metrics all defined (E/RL/RC = 1.0) -> 7.6
    ['temporal partial',     1.0, 0.71, 0.704, 0.275, 0.275, 0.275, 1.0, 1.0, 1.0, -1, -1, -1, -1, -1, 7.6],
];
