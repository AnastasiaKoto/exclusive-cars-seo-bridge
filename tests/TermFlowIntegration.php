<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/wp-load.php';

use ExclusiveCars\SeoBridge\Security\Capabilities;

$serviceUsers = get_users(['role' => 'exclusive_cars_seo_service', 'fields' => 'ID', 'number' => 1]);
$adminUsers = get_users(['role' => 'administrator', 'fields' => 'ID', 'number' => 1]);
if ($serviceUsers === [] || $adminUsers === [] || !taxonomy_exists('catalog') || !defined('WPSEO_VERSION')) {
    throw new RuntimeException('Required service user, administrator, catalog taxonomy, or Yoast is unavailable.');
}

$serviceUserId = (int) $serviceUsers[0];
$adminUserId = (int) $adminUsers[0];
$slug = 'seo-bridge-term-test-' . bin2hex(random_bytes(5));
$termId = 0;
$failures = [];
$results = [];

function termTestRequest(string $method, string $path, ?array $payload = null): WP_REST_Response
{
    $request = new WP_REST_Request($method, $path);
    if ($payload !== null) {
        $request->set_header('Content-Type', 'application/json');
        $request->set_body((string) wp_json_encode($payload));
    }
    return rest_do_request($request);
}

function termTestCheck(string $name, WP_REST_Response $response, int $status, ?string $code = null): array
{
    global $results, $failures;
    $data = $response->get_data();
    $actualCode = is_array($data) ? ($data['code'] ?? null) : null;
    $passed = $response->get_status() === $status && ($code === null || $actualCode === $code);
    $results[] = sprintf('%s: %s (HTTP %d%s)', $name, $passed ? 'PASS' : 'FAIL', $response->get_status(), $actualCode ? ', ' . $actualCode : '');
    if (!$passed) {
        $failures[] = $name . ': expected HTTP ' . $status . ($code ? ', ' . $code : '');
    }
    return is_array($data) ? $data : [];
}

function termTestAssert(string $name, bool $condition): void
{
    global $results, $failures;
    $results[] = $name . ': ' . ($condition ? 'PASS' : 'FAIL');
    if (!$condition) {
        $failures[] = $name;
    }
}

try {
    wp_set_current_user($serviceUserId);
    if (!current_user_can(Capabilities::READ) || !current_user_can(Capabilities::WRITE)) {
        throw new RuntimeException('Service user lacks SEO Bridge permissions.');
    }

    $created = wp_insert_term('SEO Bridge term test ' . $slug, 'catalog', [
        'slug' => $slug,
        'description' => 'Before test',
    ]);
    if (is_wp_error($created)) {
        throw new RuntimeException('Cannot create test term: ' . $created->get_error_message());
    }
    $termId = (int) $created['term_id'];
    $path = '/seo-bridge/v1/terms/catalog/' . $termId;

    $listing = termTestCheck('Read terms', termTestRequest('GET', '/seo-bridge/v1/terms'), 200);
    $before = null;
    foreach ($listing['items'] ?? [] as $item) {
        if (($item['id'] ?? null) === $termId) {
            $before = $item;
            break;
        }
    }
    if (!is_array($before) || !isset($before['version'])) {
        throw new RuntimeException('New term is absent from the REST listing.');
    }

    $write = termTestCheck('Apply core and Yoast', termTestRequest('PATCH', $path, [
        'expected_version' => $before['version'],
        'fields' => [
            'core' => ['description' => 'Updated test description'],
            'yoast' => ['title' => 'Test SEO title', 'description' => 'Test SEO description'],
        ],
    ]), 200);
    termTestAssert('Applied values verified', ($write['status'] ?? null) === 'applied'
        && ($write['verification'] ?? null) === 'matched'
        && ($write['after']['core']['description'] ?? null) === 'Updated test description'
        && ($write['after']['yoast']['title']['raw'] ?? null) === 'Test SEO title'
        && ($write['after']['yoast']['description']['raw'] ?? null) === 'Test SEO description');
    termTestAssert('Yoast rendered values refreshed',
        str_contains((string) ($write['after']['yoast']['title']['rendered'] ?? ''), 'Test SEO title')
        && str_contains((string) ($write['after']['yoast']['description']['rendered'] ?? ''), 'Test SEO description'));
    $results[] = 'Rendered title: ' . var_export($write['after']['yoast']['title']['rendered'] ?? null, true);
    $results[] = 'Rendered description: ' . var_export($write['after']['yoast']['description']['rendered'] ?? null, true);
    $results[] = 'edited_term listeners: ' . var_export(has_action('edited_term'), true);
    $indexable = $GLOBALS['wpdb']->get_row($GLOBALS['wpdb']->prepare(
        "SELECT title, description FROM {$GLOBALS['wpdb']->prefix}yoast_indexable WHERE object_type = 'term' AND object_id = %d",
        $termId
    ));
    $results[] = 'Indexable title: ' . var_export($indexable?->title, true);
    $results[] = 'Indexable description: ' . var_export($indexable?->description, true);
    $probe = proc_open(
        [PHP_BINARY, __DIR__ . '/TermRenderedProbe.php', (string) $termId],
        [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
        $pipes
    );
    $freshPresentation = null;
    if (is_resource($probe)) {
        fclose($pipes[0]);
        $probeOutput = stream_get_contents($pipes[1]);
        $probeError = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $probeExit = proc_close($probe);
        if ($probeExit === 0) {
            $freshPresentation = json_decode($probeOutput, true);
        } else {
            $results[] = 'Fresh Yoast probe error: ' . trim($probeError);
        }
    }
    termTestAssert('Fresh Yoast presentation refreshed',
        is_array($freshPresentation)
        && str_contains((string) ($freshPresentation['title'] ?? ''), 'Test SEO title')
        && str_contains((string) ($freshPresentation['description'] ?? ''), 'Test SEO description'));

    $currentVersion = $write['new_version'] ?? $before['version'];
    $noChange = termTestCheck('No change', termTestRequest('PATCH', $path, [
        'expected_version' => $currentVersion,
        'fields' => ['core' => ['description' => 'Updated test description']],
    ]), 200);
    termTestAssert('No change status', ($noChange['status'] ?? null) === 'no_change');

    termTestCheck('Version conflict', termTestRequest('PATCH', $path, [
        'expected_version' => $before['version'],
        'fields' => ['core' => ['description' => 'Stale edit']],
    ]), 409, 'version_conflict');

    termTestCheck('Read-only core field', termTestRequest('PATCH', $path, [
        'expected_version' => $currentVersion,
        'fields' => ['core' => ['name' => 'Forbidden edit']],
    ]), 422, 'field_not_writable');

    termTestCheck('Read-only Yoast field', termTestRequest('PATCH', $path, [
        'expected_version' => $currentVersion,
        'fields' => ['yoast' => ['canonical' => 'https://example.test/']],
    ]), 422, 'field_not_writable');

    termTestCheck('Unsupported field group', termTestRequest('PATCH', $path, [
        'expected_version' => $currentVersion,
        'fields' => ['acf' => ['field_x' => 'value']],
    ]), 400, 'invalid_request');

    termTestCheck('Empty fields', termTestRequest('PATCH', $path, [
        'expected_version' => $currentVersion,
        'fields' => [],
    ]), 400);

    termTestCheck('Malformed version', termTestRequest('PATCH', $path, [
        'expected_version' => 'wrong',
        'fields' => ['core' => ['description' => 'No edit']],
    ]), 400);

    termTestCheck('Missing version', termTestRequest('PATCH', $path, [
        'fields' => ['core' => ['description' => 'No edit']],
    ]), 400);

    termTestCheck('Invalid Yoast value type', termTestRequest('PATCH', $path, [
        'expected_version' => $currentVersion,
        'fields' => ['yoast' => ['title' => 17]],
    ]), 400, 'invalid_request');

    termTestCheck('Oversized request body', termTestRequest('PATCH', $path, [
        'expected_version' => $currentVersion,
        'fields' => ['core' => ['description' => str_repeat('x', 1048576)]],
    ]), 400, 'invalid_request');

    termTestCheck('Missing term', termTestRequest('PATCH', '/seo-bridge/v1/terms/catalog/2147483647', [
        'expected_version' => $currentVersion,
        'fields' => ['core' => ['description' => 'No edit']],
    ]), 404, 'object_not_found');

    termTestCheck('Unsupported taxonomy', termTestRequest('PATCH', '/seo-bridge/v1/terms/seo_bridge_unknown/' . $termId, [
        'expected_version' => $currentVersion,
        'fields' => ['core' => ['description' => 'No edit']],
    ]), 422, 'field_not_writable');

    $blockWrite = static function ($newValue, $oldValue) use ($termId) {
        return ($newValue['catalog'][$termId]['wpseo_title'] ?? null) === 'Blocked SEO title'
            ? $oldValue
            : $newValue;
    };
    add_filter('pre_update_option_wpseo_taxonomy_meta', $blockWrite, 10, 2);
    try {
        $failed = termTestCheck('Failed Yoast write', termTestRequest('PATCH', $path, [
            'expected_version' => $currentVersion,
            'fields' => [
                'core' => ['description' => 'Transient description'],
                'yoast' => ['title' => 'Blocked SEO title'],
            ],
        ]), 500, 'apply_failed');
        termTestAssert('Compensation reported', str_contains((string) ($failed['message'] ?? ''), 'compensation: restored'));
    } finally {
        remove_filter('pre_update_option_wpseo_taxonomy_meta', $blockWrite, 10);
    }
    $afterFailure = get_term($termId, 'catalog');
    $yoastOption = get_option('wpseo_taxonomy_meta', []);
    termTestAssert('Changed fields restored', $afterFailure instanceof WP_Term
        && $afterFailure->description === 'Updated test description'
        && ($yoastOption['catalog'][$termId]['wpseo_title'] ?? null) === 'Test SEO title');

    $deleteTitle = termTestCheck('Delete Yoast title with null', termTestRequest('PATCH', $path, [
        'expected_version' => $currentVersion,
        'fields' => ['yoast' => ['title' => null]],
    ]), 200);
    $yoastOption = get_option('wpseo_taxonomy_meta', []);
    termTestAssert('Null removes key, preserves other field',
        !array_key_exists('wpseo_title', $yoastOption['catalog'][$termId] ?? [])
        && ($yoastOption['catalog'][$termId]['wpseo_desc'] ?? null) === 'Test SEO description'
        && ($deleteTitle['after']['yoast']['title']['exists'] ?? null) === false);

    wp_set_current_user(0);
    termTestCheck('Anonymous write denied', termTestRequest('PATCH', $path, [
        'expected_version' => $currentVersion,
        'fields' => ['core' => ['description' => 'No edit']],
    ]), 401);

    wp_set_current_user($adminUserId);
    termTestCheck('User without bridge permission denied', termTestRequest('PATCH', $path, [
        'expected_version' => $currentVersion,
        'fields' => ['core' => ['description' => 'No edit']],
    ]), 403);
} finally {
    wp_set_current_user($serviceUserId);
    if ($termId > 0) {
        $deleted = wp_delete_term($termId, 'catalog');
        termTestAssert('Temporary term deleted', $deleted === true);
        $all = get_option('wpseo_taxonomy_meta', []);
        if (is_array($all) && isset($all['catalog'][$termId])) {
            unset($all['catalog'][$termId]);
            if ($all['catalog'] === []) {
                unset($all['catalog']);
            }
            $all['wpseo_already_validated'] = true;
            update_option('wpseo_taxonomy_meta', $all);
        }
    }
}

foreach ($results as $result) {
    echo $result, PHP_EOL;
}
if ($failures !== []) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}
