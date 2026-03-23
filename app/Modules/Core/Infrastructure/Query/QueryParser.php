<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Query;

use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Parse API query parameters for filtering, sorting, and searching.
 */
final readonly class QueryParser
{
    /**
     * @var array<string, mixed>
     */
    private array $params;

    public function __construct(Request $request)
    {
        $this->params = $request->getQueryParams();
    }

    /**
     * Get filter parameters.
     *
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return $this->params['filter'] ?? [];
    }

    /**
     * Get sort parameters.
     *
     * @return array<string, string> field => direction (asc/desc)
     */
    public function sorts(): array
    {
        $sort = $this->params['sort'] ?? null;

        if ($sort === null) {
            return [];
        }

        $sorts = [];
        $fields = explode(',', $sort);

        foreach ($fields as $field) {
            $field = trim($field);
            if (str_starts_with($field, '-')) {
                $sorts[substr($field, 1)] = 'desc';
            } elseif (str_starts_with($field, '+')) {
                $sorts[substr($field, 1)] = 'asc';
            } else {
                $sorts[$field] = 'asc';
            }
        }

        return $sorts;
    }

    /**
     * Get search query.
     */
    public function search(): ?string
    {
        $search = $this->params['search'] ?? null;

        return $search !== '' && $search !== null ? $search : null;
    }

    /**
     * Get fields to select.
     *
     * @return array<int, string>|null
     */
    public function fields(): ?array
    {
        $fields = $this->params['fields'] ?? null;

        if ($fields === null || $fields === '') {
            return null;
        }

        return array_map(trim(...), explode(',', $fields));
    }

    /**
     * Get range filters.
     *
     * @return array<string, array{min: mixed, max: mixed}>
     */
    public function ranges(): array
    {
        $ranges = $this->params['range'] ?? [];
        $result = [];

        foreach ($ranges as $field => $value) {
            if (is_string($value) && str_contains($value, ',')) {
                [$min, $max] = explode(',', $value, 2);
                $result[$field] = [
                    'min' => $this->castValue(trim($min)),
                    'max' => $this->castValue(trim($max)),
                ];
            }
        }

        return $result;
    }

    /**
     * Get include relationships.
     *
     * @return array<int, string>
     */
    public function includes(): array
    {
        $include = $this->params['include'] ?? null;

        if ($include === null || $include === '') {
            return [];
        }

        return array_map(trim(...), explode(',', (string) $include));
    }

    /**
     * Get pagination parameters.
     *
     * @return array{page: int, perPage: int}
     */
    public function pagination(): array
    {
        $page = max(1, (int) ($this->params['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($this->params['per_page'] ?? 15)));

        return [
            'page' => $page,
            'perPage' => $perPage,
        ];
    }

    /**
     * Get per-page limit.
     */
    public function perPage(int $default = 15, int $max = 100): int
    {
        return max(1, min($max, (int) ($this->params['per_page'] ?? $default)));
    }

    /**
     * Check if query has any parameters.
     */
    public function isEmpty(): bool
    {
        return $this->params === [];
    }

    /**
     * Get raw query parameters.
     *
     * @return array<string, mixed>
     */
    public function raw(): array
    {
        return $this->params;
    }

    /**
     * Cast string value to appropriate type.
     */
    private function castValue(string $value): mixed
    {
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        $lower = strtolower($value);
        if ($lower === 'true' || $lower === 'yes') {
            return true;
        }

        if ($lower === 'false' || $lower === 'no') {
            return false;
        }

        if ($lower === 'null') {
            return null;
        }

        return $value;
    }
}
