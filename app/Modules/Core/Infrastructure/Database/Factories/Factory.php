<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Database\Factories;

use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Model;

/**
 * Base Factory class for creating model instances with fake data.
 *
 * @template TModel of Model
 */
abstract class Factory
{
    protected Faker $faker;

    protected array $stateCallbacks = [];

    public function __construct(?Faker $faker = null)
    {
        $this->faker = $faker ?? \Faker\Factory::create();
    }

    /**
     * Get the model class name.
     *
     * @return class-string<TModel>
     */
    abstract protected function model(): string;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    abstract protected function definition(): array;

    /**
     * Apply state modifications.
     *
     * @param  callable(array<string, mixed>): array<string, mixed>  $callback
     */
    final public function state(callable $callback): static
    {
        $newFactory = clone $this;
        if (! isset($newFactory->stateCallbacks)) {
            $newFactory->stateCallbacks = [];
        }
        $newFactory->stateCallbacks[] = $callback;

        return $newFactory;
    }

    /**
     * Create a new model instance.
     *
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    final public function make(array $attributes = []): Model
    {
        $modelClass = $this->model();
        $definition = $this->definition();

        // Apply state callbacks
        if (isset($this->stateCallbacks) && is_array($this->stateCallbacks)) {
            foreach ($this->stateCallbacks as $callback) {
                $stateAttributes = $callback($definition);
                $definition = array_merge($definition, $stateAttributes);
            }
        }

        $attributes = array_merge($definition, $attributes);

        return new $modelClass($attributes);
    }

    /**
     * Create and persist a new model instance.
     *
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    final public function create(array $attributes = []): Model
    {
        $model = $this->make($attributes);
        $model->save();

        return $model;
    }

    /**
     * Create multiple model instances.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<int, TModel>
     */
    final public function createMany(int $count, array $attributes = []): array
    {
        $models = [];

        for ($i = 0; $i < $count; $i++) {
            $models[] = $this->create($attributes);
        }

        return $models;
    }

    /**
     * Get Faker instance.
     */
    protected function faker(): Faker
    {
        return $this->faker;
    }
}
