<?php

namespace Larelastic\Elastic\Tests;

use Larelastic\Elastic\Services\NestedQueryService;
use Larelastic\Elastic\Services\QueryService;

/**
 * QueryBuilder picks a service at runtime and calls it statically, so a method
 * removed from a service is not a compile error and stays invisible until a
 * request reaches that path. Every distance search was broken this way for
 * months because buildGeoQuery was dropped from both services.
 *
 * This reads the builder's source and asserts the methods it calls exist. A
 * builder method that can resolve to either service has to be satisfied by
 * both of them.
 */
class QueryBuilderContractTest extends AbstractTestCase
{
    public function test_every_service_method_the_builder_calls_exists(): void
    {
        $checked = 0;

        foreach ($this->builderMethods() as $name => $body) {
            preg_match_all('/\$service::(\w+)\(/', $body, $matches);

            $usesNestedService = str_contains($body, 'NestedQueryService::class');

            foreach (array_unique($matches[1]) as $method) {
                $checked++;

                $this->assertTrue(
                    method_exists(QueryService::class, $method),
                    "QueryBuilder::$name() calls QueryService::$method(), which does not exist."
                );

                if ($usesNestedService) {
                    $this->assertTrue(
                        method_exists(NestedQueryService::class, $method),
                        "QueryBuilder::$name() can resolve to NestedQueryService::$method(), which does not exist."
                    );
                }
            }
        }

        $this->assertGreaterThan(0, $checked, 'Found no service calls to check. Has the builder been rewritten?');
    }

    /**
     * @return array<string, string> method name => body
     */
    private function builderMethods(): array
    {
        $source = file_get_contents(dirname(__DIR__) . '/src/Builders/QueryBuilder.php');

        $chunks = preg_split(
            '/\n    (?:public|private|protected) function (\w+)/',
            $source,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );

        $methods = [];

        // The split yields the preamble, then alternating name and body.
        for ($i = 1; $i < count($chunks); $i += 2) {
            $methods[$chunks[$i]] = $chunks[$i + 1] ?? '';
        }

        return $methods;
    }
}
