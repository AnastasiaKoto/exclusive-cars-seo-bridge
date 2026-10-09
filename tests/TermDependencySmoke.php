<?php
declare(strict_types=1);

class WP_Term
{
    public int $term_id = 7;
    public string $taxonomy = 'catalog';
    public string $name = 'Test';
    public string $slug = 'test';
    public string $description = 'Before';
    public int $parent = 0;
}

class WP_REST_Request
{
    public function __construct(private array $params) {}
    public function get_body(): string { return '{}'; }
    public function get_param(string $name): mixed { return $this->params[$name] ?? null; }
}

class WP_REST_Response
{
    public function __construct(public mixed $data, public int $status) {}
}

class WP_Error
{
    public function __construct(public string $code, public string $message, public array $data) {}
}

function get_taxonomies(array $args, string $output): array { return ['catalog' => 'catalog']; }
function get_term(int $id, string $taxonomy): ?WP_Term { return $id === 7 && $taxonomy === 'catalog' ? new WP_Term() : null; }
function get_term_link(WP_Term $term): string { return '/catalog/test'; }
function is_wp_error(mixed $value): bool { return false; }
function wp_json_encode(mixed $value, int $flags): string|false { return json_encode($value, $flags); }

spl_autoload_register(static function (string $class): void {
    $prefix = 'ExclusiveCars\\SeoBridge\\';
    if (str_starts_with($class, $prefix)) {
        require dirname(__DIR__) . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    }
});

$policy = new ExclusiveCars\SeoBridge\Security\FieldPolicy();
$core = new ExclusiveCars\SeoBridge\Write\CoreTermFieldWriter($policy);
$yoast = new ExclusiveCars\SeoBridge\Write\YoastTermFieldWriter($policy);
$states = new ExclusiveCars\SeoBridge\Content\TermStateCollector(
    new ExclusiveCars\SeoBridge\Content\PostVersionCalculator(),
    new ExclusiveCars\SeoBridge\Content\YoastTermsValueCollector()
);
$updater = new ExclusiveCars\SeoBridge\Write\TermUpdater(
    new ExclusiveCars\SeoBridge\Inventory\TaxonomyCollector(),
    $core,
    $yoast,
    new ExclusiveCars\SeoBridge\Write\TermStateRestorer($core, $yoast),
    $states
);
$controller = new ExclusiveCars\SeoBridge\Rest\TermUpdateController($updater);
$version = $states->collect(new WP_Term())['version'];
$result = $controller->update(new WP_REST_Request([
    'term_id' => 7,
    'term_taxonomy' => 'catalog',
    'expected_version' => $version,
    'fields' => ['yoast' => ['title' => 'Unavailable']],
]));
if (!$result instanceof WP_Error || $result->code !== 'dependency_unavailable' || $result->data['status'] !== 424) {
    throw new RuntimeException('Expected dependency_unavailable HTTP 424.');
}
echo "Missing Yoast dependency: PASS (HTTP 424, dependency_unavailable)\n";
