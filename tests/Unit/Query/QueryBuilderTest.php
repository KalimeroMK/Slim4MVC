<?php

declare(strict_types=1);

namespace Tests\Unit\Query;

use App\Modules\Core\Infrastructure\Query\QueryBuilder;
use App\Modules\Core\Infrastructure\Query\QueryParser;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class QueryBuilderTest extends TestCase
{
    public function test_query_parser_parses_filters(): void
    {
        $serverRequest = $this->createRequest([
            'filter' => [
                'status' => 'active',
                'role' => 'admin',
            ],
        ]);

        $queryParser = new QueryParser($serverRequest);

        $this->assertSame(['status' => 'active', 'role' => 'admin'], $queryParser->filters());
    }

    public function test_query_parser_parses_sort_ascending(): void
    {
        $serverRequest = $this->createRequest(['sort' => 'name']);
        $queryParser = new QueryParser($serverRequest);

        $this->assertSame(['name' => 'asc'], $queryParser->sorts());
    }

    public function test_query_parser_parses_sort_descending(): void
    {
        $serverRequest = $this->createRequest(['sort' => '-created_at']);
        $queryParser = new QueryParser($serverRequest);

        $this->assertSame(['created_at' => 'desc'], $queryParser->sorts());
    }

    public function test_query_parser_parses_multiple_sorts(): void
    {
        $serverRequest = $this->createRequest(['sort' => '-created_at,name']);
        $queryParser = new QueryParser($serverRequest);

        $this->assertSame(['created_at' => 'desc', 'name' => 'asc'], $queryParser->sorts());
    }

    public function test_query_parser_parses_search(): void
    {
        $serverRequest = $this->createRequest(['search' => 'john doe']);
        $queryParser = new QueryParser($serverRequest);

        $this->assertSame('john doe', $queryParser->search());
    }

    public function test_query_parser_returns_null_for_empty_search(): void
    {
        $serverRequest = $this->createRequest(['search' => '']);
        $queryParser = new QueryParser($serverRequest);

        $this->assertNull($queryParser->search());
    }

    public function test_query_parser_parses_fields(): void
    {
        $serverRequest = $this->createRequest(['fields' => 'id,name,email']);
        $queryParser = new QueryParser($serverRequest);

        $this->assertSame(['id', 'name', 'email'], $queryParser->fields());
    }

    public function test_query_parser_parses_includes(): void
    {
        $serverRequest = $this->createRequest(['include' => 'posts,comments']);
        $queryParser = new QueryParser($serverRequest);

        $this->assertSame(['posts', 'comments'], $queryParser->includes());
    }

    public function test_query_parser_parses_ranges(): void
    {
        $serverRequest = $this->createRequest([
            'range' => [
                'price' => '10,100',
                'age' => '18,65',
            ],
        ]);
        $queryParser = new QueryParser($serverRequest);

        $ranges = $queryParser->ranges();

        $this->assertEquals(['min' => 10, 'max' => 100], $ranges['price']);
        $this->assertEquals(['min' => 18, 'max' => 65], $ranges['age']);
    }

    public function test_query_parser_pagination_defaults(): void
    {
        $serverRequest = $this->createRequest([]);
        $queryParser = new QueryParser($serverRequest);

        $pagination = $queryParser->pagination();

        $this->assertEquals(1, $pagination['page']);
        $this->assertEquals(15, $pagination['perPage']);
    }

    public function test_query_parser_pagination_custom(): void
    {
        $serverRequest = $this->createRequest(['page' => '3', 'per_page' => '50']);
        $queryParser = new QueryParser($serverRequest);

        $pagination = $queryParser->pagination();

        $this->assertEquals(3, $pagination['page']);
        $this->assertEquals(50, $pagination['perPage']);
    }

    public function test_query_parser_pagination_limits_per_page(): void
    {
        $serverRequest = $this->createRequest(['per_page' => '200']);
        $queryParser = new QueryParser($serverRequest);

        $this->assertSame(100, $queryParser->perPage(15, 100));
    }

    public function test_query_parser_returns_raw_filter_values(): void
    {
        // QueryParser returns raw values - casting happens in QueryBuilder
        $serverRequest = $this->createRequest([
            'filter' => [
                'active' => 'true',
                'count' => '42',
            ],
        ]);
        $queryParser = new QueryParser($serverRequest);
        $filters = $queryParser->filters();

        $this->assertSame('true', $filters['active']);
        $this->assertSame('42', $filters['count']);
    }

    public function test_query_parser_casts_range_values(): void
    {
        // Range values are cast to appropriate types
        $serverRequest = $this->createRequest([
            'range' => [
                'quantity' => '10,100',
                'price' => '9.99,99.99',
            ],
        ]);
        $queryParser = new QueryParser($serverRequest);
        $ranges = $queryParser->ranges();

        $this->assertSame(10, $ranges['quantity']['min']);
        $this->assertSame(100, $ranges['quantity']['max']);
        $this->assertEqualsWithDelta(9.99, $ranges['price']['min'], PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(99.99, $ranges['price']['max'], PHP_FLOAT_EPSILON);
    }

    public function test_query_parser_returns_raw_params(): void
    {
        $params = ['filter' => ['name' => 'test'], 'sort' => '-id'];
        $serverRequest = $this->createRequest($params);
        $queryParser = new QueryParser($serverRequest);

        $this->assertSame($params, $queryParser->raw());
    }

    public function test_query_parser_is_empty(): void
    {
        $serverRequest = $this->createRequest([]);
        $queryParser = new QueryParser($serverRequest);

        $this->assertTrue($queryParser->isEmpty());
    }

    public function test_query_builder_uses_config(): void
    {
        $serverRequest = $this->createRequest(['filter' => ['name' => 'test']]);
        $config = [
            'filterable' => ['name', 'email'],
            'sortable' => ['created_at'],
            'searchable' => ['name', 'email'],
        ];

        $queryBuilder = new QueryBuilder($serverRequest, $config);

        $this->assertInstanceOf(QueryBuilder::class, $queryBuilder);
    }

    public function test_query_helper_functions_exist(): void
    {
        $serverRequest = $this->createRequest([]);

        $this->assertInstanceOf(QueryParser::class, query_parser($serverRequest));
        $this->assertInstanceOf(QueryBuilder::class, query_builder($serverRequest));
    }

    private function createRequest(array $queryParams): ServerRequestInterface
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn($queryParams);

        return $request;
    }
}
