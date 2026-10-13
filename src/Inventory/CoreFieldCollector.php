<?php
declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Inventory;

use ExclusiveCars\SeoBridge\Contract\PostFields;
use ExclusiveCars\SeoBridge\Contract\TermFields;
use ExclusiveCars\SeoBridge\Security\FieldPolicy;

final class CoreFieldCollector
{
    public function __construct(private FieldPolicy $fieldPolicy)
    {
    }

    public function collect(array $postTypes, array $taxonomies): array
    {
        $result = [];

        foreach ($postTypes as $postType) {
            foreach (PostFields::DEFINITIONS as $name => $type) {
                $result[] = [
                    'object_kind' => 'post',
                    'source_type' => $postType['name'],
                    'name' => $this->fieldName($name, [
                        PostFields::ID,
                        PostFields::MODIFIED_AT,
                        PostFields::URL,
                        PostFields::VERSION,
                    ]),
                    'source' => 'wordpress',
                    'type' => $type,
                    'nullable' => $name === PostFields::URL,
                    'access' => $this->fieldPolicy->post($postType['name'], 'core', $name),
                ];
            }
        }

        foreach ($taxonomies as $taxonomy) {
            foreach (TermFields::DEFINITIONS as $name => $type) {
                $result[] = [
                    'object_kind' => 'term',
                    'source_type' => $taxonomy['name'],
                    'name' => $this->fieldName($name, [
                        TermFields::ID,
                        TermFields::URL,
                        TermFields::VERSION,
                    ]),
                    'source' => 'wordpress',
                    'type' => $type,
                    'nullable' => $name === TermFields::URL,
                    'access' => $this->fieldPolicy->term($taxonomy['name'], 'core', $name),
                ];
            }
        }

        return $result;
    }

    private function fieldName(string $name, array $topLevel): string
    {
        return in_array($name, $topLevel, true)
            ? $name
            : 'core.' . $name;
    }
}
