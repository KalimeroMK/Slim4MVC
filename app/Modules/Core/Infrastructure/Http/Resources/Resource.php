<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Http\Resources;

/**
 * @template T of object
 */
abstract class Resource
{
    /**
     * Transform a single resource.
     *
     * @param  T  $resource
     * @return array<string, mixed>
     */
    abstract public static function make(mixed $resource): array;

    /**
     * Transform a collection of resources.
     *
     * @param  iterable<T>  $collection
     * @return list<array<string, mixed>>
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
     *
     * @param  T|null  $resource
     * @return array<string, mixed>|null
     */
    final public static function when(mixed $resource): ?array
    {
        if ($resource === null) {
            return null;
        }

        return static::make($resource);
    }
}
