<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/wp-load.php';

$termId = (int) ($argv[1] ?? 0);
if ($termId < 1) {
    exit(2);
}

$presentation = YoastSEO()->meta->for_term($termId);
if ($presentation === false) {
    exit(3);
}

echo wp_json_encode([
    'title' => $presentation->title,
    'description' => $presentation->description,
]);
