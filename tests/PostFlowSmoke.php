<?php
declare(strict_types=1);

const WPSEO_VERSION = 'test';
$GLOBALS['post'] = (object) [
    'ID' => 7, 'post_type' => 'cars', 'post_status' => 'publish',
    'post_name' => 'car', 'post_title' => 'Old', 'post_content' => 'Body',
    'post_excerpt' => '', 'post_parent' => 0,
];
$GLOBALS['acf'] = ['field_668f86ae37a5f' => 'Old ACF'];
$GLOBALS['meta'] = ['_yoast_wpseo_title' => 'Old SEO', '_yoast_wpseo_metadesc' => 'Old description'];
$GLOBALS['fail_meta'] = false;

#[AllowDynamicProperties] class WP_Post { public function __construct(object $source) { foreach ($source as $key => $value) $this->$key = $value; } }
function get_post(int $id): ?WP_Post { return $id === 7 ? new WP_Post($GLOBALS['post']) : null; }
function get_post_types(array $args, string $output): array { return ['cars' => 'cars', 'attachment' => 'attachment']; }
function get_permalink(WP_Post $post): string { return '/car'; }
function get_post_modified_time(string $format, bool $gmt, WP_Post $post): string { return '2026-01-01T00:00:00+00:00'; }
function wp_json_encode($value, int $flags): string|false { return json_encode($value, $flags); }
function get_field_objects(int $id, bool $format): array { return ['Description' => ['key' => 'field_668f86ae37a5f', 'name' => 'Description', 'value' => $GLOBALS['acf']['field_668f86ae37a5f']]]; }
function update_field(string $key, string $value, int $id): bool { $GLOBALS['acf'][$key] = $value; return true; }
function get_field(string $key, int $id, bool $format): ?string { return $GLOBALS['acf'][$key] ?? null; }
function metadata_exists(string $type, int $id, string $key): bool { return array_key_exists($key, $GLOBALS['meta']); }
function get_post_meta(int $id, string $key, bool $single): ?string { return $GLOBALS['meta'][$key] ?? null; }
function update_post_meta(int $id, string $key, string $value): bool { if ($GLOBALS['fail_meta']) { $GLOBALS['fail_meta'] = false; return false; } $GLOBALS['meta'][$key] = $value; return true; }
function delete_post_meta(int $id, string $key): bool { unset($GLOBALS['meta'][$key]); return true; }
function YoastSEO(): object { return (object) ['meta' => new class { public function for_post(int $id): object { return (object) ['title' => $GLOBALS['meta']['_yoast_wpseo_title'] ?? null, 'description' => $GLOBALS['meta']['_yoast_wpseo_metadesc'] ?? null]; } }]; }
function wp_slash(array $data): array { return $data; }
function wp_update_post(array $data, bool $error): int { foreach (['post_title', 'post_content'] as $key) if (isset($data[$key])) $GLOBALS['post']->$key = $data[$key]; return 7; }
function is_wp_error($value): bool { return false; }

spl_autoload_register(function (string $class): void {
    $prefix = 'ExclusiveCars\\SeoBridge\\';
    if (str_starts_with($class, $prefix)) {
        require __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    }
});

use ExclusiveCars\SeoBridge\Content\ACFValueCollector;
use ExclusiveCars\SeoBridge\Content\PostStatesCollector;
use ExclusiveCars\SeoBridge\Content\PostVersionCalculator;
use ExclusiveCars\SeoBridge\Content\YoastValueCollector;
use ExclusiveCars\SeoBridge\Inventory\PostTypeCollector;
use ExclusiveCars\SeoBridge\Security\FieldPolicy;
use ExclusiveCars\SeoBridge\Write\AcfFieldWriter;
use ExclusiveCars\SeoBridge\Write\CoreFieldWriter;
use ExclusiveCars\SeoBridge\Write\PostStateRestorer;
use ExclusiveCars\SeoBridge\Write\PostUpdater;
use ExclusiveCars\SeoBridge\Write\YoastFieldWriter;
use ExclusiveCars\SeoBridge\Exception\VersionMismatchException;

$states = new PostStatesCollector(new ACFValueCollector(), new YoastValueCollector(), new PostVersionCalculator());
$policy = new FieldPolicy();
$core = new CoreFieldWriter($policy);
$acfWriter = new AcfFieldWriter($policy);
$yoast = new YoastFieldWriter($policy);
$updater = new PostUpdater(new PostTypeCollector(), $states, $core, $acfWriter, $yoast, new PostStateRestorer($core, $acfWriter, $yoast));
$before = $states->collect(get_post(7));
assert(isset($before['core']['title'], $before['acf']['field_668f86ae37a5f']['value'], $before['yoast']['title']['raw']));
$result = $updater->update(7, $before['version'], ['core' => ['title' => 'New'], 'acf' => ['field_668f86ae37a5f' => 'New ACF'], 'yoast' => ['title' => 'New SEO']]);
assert($result['status'] === 'applied' && $result['verification'] === 'matched');
$noChange = $updater->update(7, $result['new_version'], ['core' => ['title' => 'New']]);
assert($noChange['status'] === 'no_change');
try { $updater->update(7, $before['version'], ['core' => ['title' => 'Other']]); throw new Exception('Expected conflict'); } catch (VersionMismatchException $expected) {}
$GLOBALS['fail_meta'] = true;
try { $updater->update(7, $result['new_version'], ['core' => ['title' => 'Transient'], 'yoast' => ['title' => 'Failure']]); throw new Exception('Expected failure'); } catch (RuntimeException $expected) { assert(str_contains($expected->getMessage(), 'restored')); }
assert($GLOBALS['post']->post_title === 'New');
$reordered = $before;
$reordered['core'] = array_reverse($before['core'], true);
$reordered['acf']['field_668f86ae37a5f']['name'] = 'Different label';
$reordered['yoast']['title']['rendered'] = 'Different rendered title';
$reordered['url'] = '/different';
assert((new PostVersionCalculator())->calculate($before) === (new PostVersionCalculator())->calculate($reordered));
echo "post flow smoke OK\n";
