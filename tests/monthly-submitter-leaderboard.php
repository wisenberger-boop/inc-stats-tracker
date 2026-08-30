<?php
/**
 * Standalone query/UI contract checks for the Group Stats monthly leaderboard.
 *
 * Run with: php tests/monthly-submitter-leaderboard.php
 */

declare(strict_types=1);

define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);

if (! function_exists('absint')) {
    function absint(mixed $value): int
    {
        return abs((int) $value);
    }
}

if (! function_exists('__')) {
    function __(string $text, string $domain = ''): string
    {
        return $text;
    }
}

if (! function_exists('esc_html')) {
    function esc_html(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (! function_exists('esc_attr')) {
    function esc_attr(mixed $value): string
    {
        return esc_html($value);
    }
}

if (! function_exists('sanitize_html_class')) {
    function sanitize_html_class(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9_-]/', '', $value) ?? '';
    }
}

if (! function_exists('wp_unique_id')) {
    function wp_unique_id(string $prefix = ''): string
    {
        static $id = 0;
        return $prefix . ++$id;
    }
}

if (! function_exists('number_format_i18n')) {
    function number_format_i18n(int|float $number): string
    {
        return number_format($number);
    }
}

if (! function_exists('ist_format_currency')) {
    function ist_format_currency(float $amount): string
    {
        return '$' . number_format($amount, 2);
    }
}

final class IST_Test_WPDB
{
    public string $prefix = 'wp_';
    public string $preparedSql = '';
    public array $preparedArgs = [];
    public array $resultRows = [
        [
            'user_id' => '12',
            'name' => 'Example Member',
            'amount' => '1250.50',
            'count' => '3',
        ],
    ];

    public function prepare(string $sql, array $args): string
    {
        $this->preparedSql = $sql;
        $this->preparedArgs = $args;
        return $sql;
    }

    public function get_results(string $sql): array
    {
        return array_map(static fn(array $row): object => (object) $row, $this->resultRows);
    }
}

$wpdb = new IST_Test_WPDB();
require_once dirname(__DIR__) . '/plugin/inc-stats-tracker/includes/class-ist-stats-query.php';

$rows = IST_Stats_Query::tyfcb_submitter_leaderboard(
    '2026-08-01',
    '2026-08-28',
    [12, 27],
    10
);

$failures = [];
$sqlAssertions = [
    'SELECT submitted_by_user_id AS user_id',
    'MAX(submitted_by_name) AS name',
    'SUM(amount) AS amount',
    'COUNT(*) AS count',
    'submitted_by_user_id IN (%d,%d)',
    'GROUP BY submitted_by_user_id',
    'ORDER BY amount DESC, count DESC, name ASC',
];
foreach ($sqlAssertions as $expected) {
    if (! str_contains($wpdb->preparedSql, $expected)) {
        $failures[] = "query is missing: $expected";
    }
}

$expectedArgs = ['2026-08-01', '2026-08-28', 12, 27, 10];
if ($wpdb->preparedArgs !== $expectedArgs) {
    $failures[] = 'query arguments or ordering are incorrect';
}

$expectedRow = [
    'user_id' => 12,
    'name' => 'Example Member',
    'amount' => 1250.5,
    'count' => 3,
];
if ($rows !== [$expectedRow]) {
    $failures[] = 'query result normalization is incorrect';
}

$wpdb->resultRows = [
    [
        'user_id' => '27',
        'name' => 'Referral Giver',
        'count' => '4',
    ],
];
$referralRows = IST_Stats_Query::referral_leaderboard(
    '2026-08-01',
    '2026-08-28',
    [12, 27],
    10
);
$referralSqlAssertions = [
    'SELECT referred_by_user_id AS user_id',
    'MAX(referred_by_name) AS name',
    'COUNT(*) AS count',
    'referred_by_user_id IN (%d,%d)',
    'GROUP BY referred_by_user_id',
    'ORDER BY count DESC',
];
foreach ($referralSqlAssertions as $expected) {
    if (! str_contains($wpdb->preparedSql, $expected)) {
        $failures[] = "referral query is missing: $expected";
    }
}
if ($wpdb->preparedArgs !== $expectedArgs) {
    $failures[] = 'referral query arguments or ordering are incorrect';
}
if ($referralRows !== [[
    'user_id' => 27,
    'name' => 'Referral Giver',
    'count' => 4,
]]) {
    $failures[] = 'referral query result normalization is incorrect';
}

$root = dirname(__DIR__);
$groupTemplate = file_get_contents($root . '/plugin/inc-stats-tracker/templates/frontend/tmpl-group-stats-reports.php');
$groupController = file_get_contents($root . '/plugin/inc-stats-tracker/frontend/class-ist-group-extension.php');
$kpiPartial = file_get_contents($root . '/plugin/inc-stats-tracker/templates/frontend/partials/tmpl-kpi-row.php');
$css = file_get_contents($root . '/plugin/inc-stats-tracker/assets/css/ist-frontend.css');

if (false === $groupTemplate || substr_count($groupTemplate, "'emphasize_month'") !== 2) {
    $failures[] = 'Closed Business and Referrals must be the emphasized MTD KPI values';
}
if (false === $groupTemplate || ! preg_match('/\'fy_label\'\s*=>\s*\$fy_ytd_label/', $groupTemplate)) {
    $failures[] = 'Closed Business Count is not labeled as fiscal-year YTD';
}
if (false === $groupTemplate || ! preg_match('/\'leaderboard\'\s*=>\s*\$tyfcb_month_submitters/', $groupTemplate)) {
    $failures[] = 'monthly submitter leaderboard is not wired to the Amount KPI';
}
if (false === $groupTemplate || ! preg_match('/\'leaderboard_id\'\s*=>\s*\'ist-group-monthly-tyfcb-leaders\'/', $groupTemplate)) {
    $failures[] = 'monthly leaderboard does not provide a stable unique heading ID';
}
if (false === $groupController || ! preg_match('/\$tyfcb_month_submitters\s*=\s*\$roster_user_ids\s*\?\s*IST_Stats_Query::tyfcb_submitter_leaderboard/s', $groupController)) {
    $failures[] = 'monthly member leaderboard is not guarded against an empty roster';
}
if (false === $groupTemplate || ! preg_match('/\'leaderboard\'\s*=>\s*\$referral_month_givers/', $groupTemplate)) {
    $failures[] = 'monthly referral leaderboard is not wired to the Referrals Given KPI';
}
if (false === $groupTemplate || ! preg_match('/\'leaderboard_id\'\s*=>\s*\'ist-group-monthly-referral-leaders\'/', $groupTemplate)) {
    $failures[] = 'monthly referral leaderboard does not provide a stable unique heading ID';
}
if (false === $groupTemplate || ! preg_match('/\'leaderboard_value_type\'\s*=>\s*\'count\'/', $groupTemplate)) {
    $failures[] = 'monthly referral leaderboard is not configured for count-only values';
}
if (false === $groupTemplate || ! str_contains($groupTemplate, "__( 'referral', 'inc-stats-tracker' )") || ! str_contains($groupTemplate, "__( 'referrals', 'inc-stats-tracker' )")) {
    $failures[] = 'monthly referral leaderboard singular/plural units are missing';
}
if (false === $groupTemplate || ! str_contains($groupTemplate, 'No referrals were given this month.')) {
    $failures[] = 'monthly referral leaderboard empty state is missing';
}
if (false === $groupController || ! preg_match('/\$referral_month_givers\s*=\s*\$roster_user_ids\s*\?\s*IST_Stats_Query::referral_leaderboard\(\s*\$month_start,\s*\$month_end,\s*\$roster_user_ids\s*\)/s', $groupController)) {
    $failures[] = 'monthly referral leaderboard is not guarded against an empty roster';
}
if (false === $groupController || ! preg_match('/\$referral_leaderboard\s*=\s*IST_Stats_Query::referral_leaderboard\(\s*\$fy_start,\s*\$fy_end,\s*\$roster_user_ids\s*\)/s', $groupController)) {
    $failures[] = 'existing fiscal-year referral leaderboard was not preserved';
}
if (false === $kpiPartial || ! str_contains($kpiPartial, '<ol class="ist-kpi-leaderboard__list" role="list">')) {
    $failures[] = 'leaderboard does not use semantic ordered-list markup';
}
if (false === $kpiPartial || ! str_contains($kpiPartial, 'No closed business submissions this month.')) {
    $failures[] = 'leaderboard empty state is missing';
}
if (false === $kpiPartial || ! str_contains($kpiPartial, "=== 'count' ? 'count' : 'amount'") || ! str_contains($kpiPartial, '$leaderboard_unit_singular')) {
    $failures[] = 'shared KPI leaderboard does not render count-only metrics';
}
if (false === $css || ! preg_match('/\.ist-kpi-card__value--month-emphasis \.ist-kpi-card__number\s*\{[^}]*font-size:\s*18px;[^}]*font-weight:\s*700;[^}]*color:\s*#1e4e8c;/s', $css)) {
    $failures[] = 'MTD emphasis does not match the FY number treatment';
}
if (false === $css || ! preg_match('/@media \( max-width: 700px \).*?\.ist-kpi-leaderboard__totals\s*\{[^}]*white-space:\s*normal;/s', $css)) {
    $failures[] = 'stacked mobile leaderboard totals cannot wrap long or translated content';
}
if (false === $groupTemplate || substr_count($groupTemplate, "ist_get_template( 'frontend/partials/tmpl-kpi-row.php'") !== 3) {
    $failures[] = 'Group Stats KPI grid must render exactly three cards';
}
if (false === $groupTemplate || ! str_contains($groupTemplate, "__( 'Closed Business', 'inc-stats-tracker' )") || str_contains($groupTemplate, 'Closed Business (Amount)') || str_contains($groupTemplate, 'Closed Business (Count)')) {
    $failures[] = 'standalone Closed Business amount/count cards were not consolidated';
}
$referralPosition = false === $groupTemplate ? false : strpos($groupTemplate, "__( 'Referrals Given', 'inc-stats-tracker' )");
$businessPosition = false === $groupTemplate ? false : strpos($groupTemplate, "__( 'Closed Business', 'inc-stats-tracker' )");
$connectPosition = false === $groupTemplate ? false : strpos($groupTemplate, "__( 'Connects Logged', 'inc-stats-tracker' )");
if (false === $referralPosition || false === $businessPosition || false === $connectPosition || ! ($referralPosition < $businessPosition && $businessPosition < $connectPosition)) {
    $failures[] = 'KPI card source/reading order must be Referrals, Closed Business, Connects';
}
if (false === $groupTemplate || ! str_contains($groupTemplate, "'fy_secondary_value'       => \$tyfcb_fy['count']") || ! str_contains($groupTemplate, "'month_secondary_value'    => \$tyfcb_month['count']")) {
    $failures[] = 'Closed Business FY/MTD submission counts are not combined with the amount card';
}
if (false === $groupTemplate || ! preg_match('/\'label\'\s*=>\s*__\( \'Connects Logged\'.*?\'full_width\'\s*=>\s*true.*?\'compact_values\'\s*=>\s*true/s', $groupTemplate)) {
    $failures[] = 'Connects card is not configured as a compact full-width row';
}
if (false === $css || ! preg_match('/\.ist-kpi-card--full\s*\{[^}]*grid-column:\s*1 \/ -1;/s', $css)) {
    $failures[] = 'full-width KPI card does not span both desktop columns';
}
if (false === $css || ! preg_match('/\.ist-kpi-card__values--inline\s*\{[^}]*grid-template-columns:\s*repeat\(2, minmax\(0, 1fr\)\);/s', $css)) {
    $failures[] = 'compact Connects values are not arranged in two desktop columns';
}
if (false === $css || ! preg_match('/@media \( max-width: 480px \).*?\.ist-kpi-card--full\s*\{[^}]*grid-column:\s*auto;.*?\.ist-kpi-card__values--inline\s*\{[^}]*display:\s*flex;/s', $css)) {
    $failures[] = 'full-width/inline KPI layout does not reset for the mobile single-column flow';
}

$renderKpi = static function (array $variables) use ($root): string {
    extract($variables, EXTR_SKIP);
    ob_start();
    include $root . '/plugin/inc-stats-tracker/templates/frontend/partials/tmpl-kpi-row.php';
    return (string) ob_get_clean();
};

$referralHtml = $renderKpi([
    'label' => 'Referrals Given',
    'month_value' => 5,
    'fy_value' => 18,
    'format' => 'count',
    'fy_label' => 'FY 2026-27',
    'month_label' => 'August 2026 (MTD)',
    'leaderboard' => [
        ['user_id' => 12, 'name' => 'Singular Member', 'count' => 1],
        ['user_id' => 27, 'name' => 'Plural Member', 'count' => 4],
    ],
    'leaderboard_id' => 'referral-render-contract',
    'leaderboard_label' => 'August 2026 (MTD) Referral Leaders',
    'leaderboard_value_type' => 'count',
    'leaderboard_unit_singular' => 'referral',
    'leaderboard_unit_plural' => 'referrals',
    'leaderboard_empty_message' => 'No referrals were given this month.',
]);
$renderAssertions = [
    'aria-labelledby="referral-render-contract"',
    '<ol class="ist-kpi-leaderboard__list" role="list">',
    '<strong>1 referral</strong>',
    '<strong>4 referrals</strong>',
];
foreach ($renderAssertions as $expected) {
    if (! str_contains($referralHtml, $expected)) {
        $failures[] = "referral render is missing: $expected";
    }
}
if (str_contains($referralHtml, '$')) {
    $failures[] = 'count-only referral leaderboard rendered a currency value';
}

$emptyReferralHtml = $renderKpi([
    'label' => 'Referrals Given',
    'month_value' => 0,
    'fy_value' => 0,
    'format' => 'count',
    'fy_label' => 'FY 2026-27',
    'month_label' => 'August 2026 (MTD)',
    'leaderboard' => [],
    'leaderboard_id' => 'empty-referral-render-contract',
    'leaderboard_value_type' => 'count',
    'leaderboard_empty_message' => 'No referrals were given this month.',
]);
if (! str_contains($emptyReferralHtml, 'No referrals were given this month.')) {
    $failures[] = 'rendered referral empty state is missing';
}

$closedBusinessHtml = $renderKpi([
    'label' => 'Closed Business',
    'month_value' => 1250.50,
    'fy_value' => 5000,
    'format' => 'currency',
    'fy_label' => 'FY 2026-27 YTD',
    'month_label' => 'August 2026 (MTD)',
    'fy_secondary_value' => 7,
    'month_secondary_value' => 1,
    'secondary_unit_singular' => 'submission',
    'secondary_unit_plural' => 'submissions',
    'leaderboard' => [
        ['user_id' => 12, 'name' => 'Closed Business Member', 'amount' => 1250.50, 'count' => 3],
    ],
    'leaderboard_id' => 'closed-business-render-contract',
]);
if (! str_contains($closedBusinessHtml, '<strong>$1,250.50</strong>') || ! str_contains($closedBusinessHtml, '<span>3 submissions</span>')) {
    $failures[] = 'Closed Business amount-plus-count rendering regressed';
}
if (! str_contains($closedBusinessHtml, '<span class="ist-kpi-card__secondary">7 submissions</span>') || ! str_contains($closedBusinessHtml, '<span class="ist-kpi-card__secondary">1 submission</span>')) {
    $failures[] = 'Closed Business FY/MTD secondary submission counts did not render correctly';
}

$connectHtml = $renderKpi([
    'label' => 'Connects Logged',
    'month_value' => 9,
    'fy_value' => 31,
    'format' => 'count',
    'fy_label' => 'FY 2026-27',
    'month_label' => 'August 2026 (MTD)',
    'full_width' => true,
    'compact_values' => true,
]);
if (! str_contains($connectHtml, 'class="ist-kpi-card ist-kpi-card--full"') || ! str_contains($connectHtml, 'class="ist-kpi-card__values ist-kpi-card__values--inline"')) {
    $failures[] = 'Connects full-width compact layout classes did not render';
}

if ($failures) {
    fwrite(STDERR, "monthly_submitter_leaderboard=failed\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo 'monthly_submitter_leaderboard=passed sql_assertions=' . (count($sqlAssertions) + count($referralSqlAssertions)) . ' ui_contracts=25 render_contracts=9' . PHP_EOL;
