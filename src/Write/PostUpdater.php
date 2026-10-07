<?php
declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Write;

use ExclusiveCars\SeoBridge\Contract\PostFields;
use ExclusiveCars\SeoBridge\Contract\PostStates;
use ExclusiveCars\SeoBridge\Content\PostStatesCollector;
use WP_Post;
use RuntimeException;
use DomainException;
use InvalidArgumentException;
use ExclusiveCars\SeoBridge\Inventory\PostTypeCollector;

final class PostUpdater
{
    private const FIELD_GROUPS = [
        'core',
        'acf',
        'yoast',
    ];

    public function __construct(
        private PostTypeCollector $postTypeCollector,
        private PostStatesCollector $postStatesCollector,
        private CoreFieldWriter $coreFieldWriter,
        private AcfFieldWriter $acfFieldWriter,
        private YoastFieldWriter $yoastFieldWriter
    ) {
    }

    public function update(int $postId, string $expectedVersion, array $fields): array
    {
        $before = $this->getPostState($postId);

        if (!hash_equals($before[PostFields::VERSION], $expectedVersion)) {
            throw new RuntimeException('Post version mismatch.');
        }
        if(empty($fields)) {
            throw new RuntimeException('Fields must be a non-empty array.');
        }
        if(array_diff(array_keys($fields), self::FIELD_GROUPS)) {
            throw new RuntimeException('Invalid field group(s) provided.');
        }

        $coreFields = $this->checkCoreFields($fields);
        $acfFields = $this->checkAcfFields($fields);
        $yoastFields = $this->checkYoastFields($fields);

        if (
            $coreFields === []
            && $acfFields === []
            && $yoastFields === []
        ) {
            throw new InvalidArgumentException(
                'At least one field must be provided.'
            );
        }

        $this->coreFieldWriter->write($postId, $coreFields);
        $this->acfFieldWriter->write($postId, $acfFields);
        $this->yoastFieldWriter->write($postId, $yoastFields);

        $after = $this->getPostState($postId);

        return [
            'before' => $before,
            'after' => $after
        ];              
    }

    private function getPostState(int $postId): array
    {
        $post = get_post($postId);

        if (!$post instanceof WP_Post) {
            throw new RuntimeException('Post not found.');
        }

        if(!in_array($post->post_type, $this->postTypeCollector->names(), true)) {
            throw new DomainException('Post type is not supported.');
        }

        if (!in_array($post->post_status, PostStates::ALLOWED, true)) {
            throw new DomainException(
                sprintf(
                    'Post status "%s" is not writable.',
                    $post->post_status
                )
            );
        }

        return $this->postStatesCollector->collect($post);
    }

    private function checkCoreFields(array $fields): array
    {
        $coreFields = $fields['core'] ?? [];
        
        if (!is_array($coreFields)) {
            throw new RuntimeException('Core fields must be an array.');
        }

        $this->coreFieldWriter->validate($coreFields);

        return $coreFields;
    }

    private function checkAcfFields(array $fields): array
    {
        $acfFields = $fields['acf'] ?? [];

        if (!is_array($acfFields)) {
            throw new RuntimeException('ACF fields must be an array.');
        }

        $this->acfFieldWriter->validate($acfFields);

        return $acfFields;
    }

    private function checkYoastFields(array $fields): array
    {
        $yoastFields = $fields['yoast'] ?? [];

        if (!is_array($yoastFields)) {
            throw new RuntimeException('Yoast fields must be an array.');
        }

        $this->yoastFieldWriter->validate($yoastFields);

        return $yoastFields;
    }
}