<?php

declare(strict_types=1);

namespace App\Http\Resources;

abstract class Resource
{
    /**
     * Transform a single resource.
     *
     * @param mixed $resource
     */
    abstract public static function make(mixed $resource): array;

    /**
     * Transform a collection of resources.
     *
     * @param iterable $collection
     * @return array<int, array>
     */
    public static function collection(iterable $collection): array
    {
        $result = [];

        foreach ($collection as $item) {
            $result[] = static::make($item);
        }

        return $result;
    }

    /**
     * Transform resource when it's not null, otherwise return null.
     *
     * @param mixed $resource
     */
    public static function when(mixed $resource): ?array
    {
        if ($resource === null) {
            return null;
        }

        return static::make($resource);
    }
}

