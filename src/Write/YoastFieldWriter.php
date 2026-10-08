<?php
declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Write;

use DomainException;
use ExclusiveCars\SeoBridge\Contract\YoastFields;
use ExclusiveCars\SeoBridge\Security\FieldPolicy;
use InvalidArgumentException;
use RuntimeException;

final class YoastFieldWriter
{
    public function __construct(private FieldPolicy $fieldPolicy)
    {
    }

    public function write(int $postId, array $fields): void
    {
        $this->validate($fields);
        foreach ($fields as $field => $value) {
            $metaKey = YoastFields::META_KEYS[$field];
            if (update_post_meta($postId, $metaKey, $value) === false
                && get_post_meta($postId, $metaKey, true) !== $value) {
                throw new RuntimeException('Yoast meta update failed.');
            }
        }
    }

    public function restore(int $postId, string $field, array $original): void
    {
        $metaKey = YoastFields::META_KEYS[$field];
        if (!$original['exists']) {
            delete_post_meta($postId, $metaKey);
            if (metadata_exists('post', $postId, $metaKey)) {
                throw new RuntimeException('Yoast meta compensation failed.');
            }
            return;
        }
        $this->write($postId, [$field => $original['raw']]);
    }

    public function validate(array $fields): void
    {
        if ($fields !== [] && (!defined('WPSEO_VERSION') || !function_exists('YoastSEO'))) {
            throw new RuntimeException('Yoast SEO is not available.', 424);
        }
        foreach ($fields as $key => $value) {
            if (!is_string($key) || $key === '' || !is_string($value)) {
                throw new InvalidArgumentException('Yoast field and value must be strings.');
            }
            if (!$this->fieldPolicy->canWriteYoast($key)) {
                throw new DomainException('Yoast field is not writable: ' . $key);
            }
        }
    }
}
