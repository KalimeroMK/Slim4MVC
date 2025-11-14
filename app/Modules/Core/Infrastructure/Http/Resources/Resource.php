<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Http\Resources;

abstract class Resource
{
    /**
     * Transform a single resource.
     */
    abstract public static function make(mixed $resource): array;

    /**
     * Transform a collection of resources.
     *
     * @return array<int, array>
     */
    final public static function collection(iterable $collection): array
    {
        $result = [];

        foreach ($collection as $item) {
            $result[] = static::make($item);
        }

        return $result;
    }

    /**
     * Transform resource when it's not null, otherwise return null.
     */
    final public static function when(mixed $resource): ?array
    {
        if ($resource === null) {
            return null;
        }

        return static::make($resource);
    }
}
