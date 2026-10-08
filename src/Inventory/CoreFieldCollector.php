<?php
declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Inventory;

use ExclusiveCars\SeoBridge\Contract\PostFields;
use ExclusiveCars\SeoBridge\Security\FieldPolicy;

final class CoreFieldCollector
{
    public function __construct(private FieldPolicy $fieldPolicy)
    {
    }

    public function collect(): array
    {
        $result = [];
        foreach (PostFields::DEFINITIONS as $name => $type) {
            $result[] = [
                'name' => in_array($name, [
                    PostFields::ID, PostFields::MODIFIED_AT,
                    PostFields::URL, PostFields::VERSION,
                ], true) ? $name : 'core.' . $name,
                'source' => 'wordpress',
                'type' => $type,
                'nullable' => $name === PostFields::URL,
                'access' => $this->fieldPolicy->core($name),
            ];
        }
        return $result;
    }
}
