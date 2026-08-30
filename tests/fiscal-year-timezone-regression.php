<?php
/**
 * Standalone regression check for prior-year YTD calendar math.
 *
 * This intentionally does not bootstrap WordPress. Run with:
 * php tests/fiscal-year-timezone-regression.php
 */

declare(strict_types=1);

date_default_timezone_set('UTC');

/**
 * Mirror the calendar-date calculation used by both member-facing controllers.
 */
function prior_equivalent_end(string $today): string
{
    $date = new DateTime($today);

    if ('02-29' === $date->format('m-d')) {
        return $date->modify('-1 year')->modify('last day of February')->format('Y-m-d');
    }

    return $date->modify('-1 year')->format('Y-m-d');
}

$cases = [
    '2026-07-02' => '2025-07-02',
    '2026-07-01' => '2025-07-01',
    '2028-02-29' => '2027-02-28',
    '2024-02-29' => '2023-02-28',
];

$failures = [];
foreach ($cases as $input => $expected) {
    $actual = prior_equivalent_end($input);
    if ($actual !== $expected) {
        $failures[] = "$input expected $expected, got $actual";
    }
}

$root = dirname(__DIR__);
$sources = [
    'plugin/inc-stats-tracker/frontend/class-ist-group-extension.php',
    'plugin/inc-stats-tracker/frontend/class-ist-profile-nav.php',
    'plugin/inc-stats-tracker/includes/class-ist-stats-query.php',
];
$unsafePattern = "wp_date( 'Y-m-d', strtotime( '-1 year', strtotime( \$today ) ) )";
foreach ($sources as $relative) {
    $contents = file_get_contents($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative));
    if (false === $contents) {
        $failures[] = "could not read $relative";
    } elseif (str_contains($contents, $unsafePattern)) {
        $failures[] = "unsafe UTC round-trip remains in $relative";
    }
}

if ($failures) {
    fwrite(STDERR, "fiscal_year_timezone_regression=failed\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo 'fiscal_year_timezone_regression=passed cases=' . count($cases) . ' sources=' . count($sources) . PHP_EOL;
