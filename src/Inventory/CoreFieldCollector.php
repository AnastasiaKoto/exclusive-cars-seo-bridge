<?php

declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Inventory;

use ExclusiveCars\SeoBridge\Security\FieldPolicy;
use ExclusiveCars\SeoBridge\Contract\PostFields;
final class CoreFieldCollector
{

    public function __construct(
        private FieldPolicy $fieldPolicy
    ) {
    }

    public function collect(): array
    {
        $result = [];

        foreach (PostFields::DEFINITIONS as $name => $type) {
            $result[] = [
                'name' => $name,
                'source' => 'wordpress',
                'type' => $type,
                'access' => $this->fieldPolicy->core($name),
            ];
        }

        return $result;
    }
}