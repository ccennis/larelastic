<?php

namespace Larelastic\Elastic\Tests;

use Larelastic\Elastic\Payloads\RawPayload;

class RawPayloadTest extends AbstractTestCase
{
    public function test_set_and_get(): void
    {
        $payload = new RawPayload();
        $payload->set('index', 'my_index');

        $this->assertEquals('my_index', $payload->get('index'));
    }

    public function test_set_nested(): void
    {
        $payload = new RawPayload();
        $payload->set('body.settings.number_of_replicas', 1);

        $result = $payload->get();
        $this->assertEquals(1, $result['body']['settings']['number_of_replicas']);
    }

    public function test_get_returns_full_payload(): void
    {
        $payload = new RawPayload();
        $payload->set('index', 'test');
        $payload->set('body.query', ['match_all' => new \stdClass()]);

        $result = $payload->get();
        $this->assertArrayHasKey('index', $result);
        $this->assertArrayHasKey('body', $result);
    }

    public function test_get_with_default(): void
    {
        $payload = new RawPayload();

        $this->assertEquals('fallback', $payload->get('missing_key', 'fallback'));
    }

    public function test_has(): void
    {
        $payload = new RawPayload();
        $payload->set('body.query.bool.must', []);

        $this->assertTrue($payload->has('body.query.bool.must'));
        $this->assertFalse($payload->has('body.query.bool.should'));
    }

    public function test_set_if_not_empty_skips_empty_array(): void
    {
        $payload = new RawPayload();
        $payload->setIfNotEmpty('body.settings', []);

        $this->assertFalse($payload->has('body.settings'));
    }

    public function test_set_if_not_empty_with_nonempty_value(): void
    {
        $payload = new RawPayload();
        $payload->setIfNotEmpty('body.settings', ['replicas' => 1]);

        $this->assertTrue($payload->has('body.settings'));
    }

    public function test_set_if_not_empty_skips_null(): void
    {
        $payload = new RawPayload();
        $payload->setIfNotEmpty('body', null);

        $this->assertFalse($payload->has('body'));
    }

    public function test_set_if_not_null_allows_empty(): void
    {
        $payload = new RawPayload();
        $payload->setIfNotNull('body', []);

        $this->assertTrue($payload->has('body'));
    }

    public function test_set_if_not_null_skips_null(): void
    {
        $payload = new RawPayload();
        $payload->setIfNotNull('body', null);

        $this->assertFalse($payload->has('body'));
    }

    public function test_add_appends(): void
    {
        $payload = new RawPayload();
        $payload->add('body', ['index' => ['_id' => 1]]);
        $payload->add('body', ['name' => 'test']);

        $result = $payload->get('body');
        $this->assertCount(2, $result);
    }

    public function test_add_if_not_empty(): void
    {
        $payload = new RawPayload();
        $payload->addIfNotEmpty('body', []);
        $payload->addIfNotEmpty('body', ['data' => true]);

        $result = $payload->get('body');
        $this->assertCount(1, $result);
    }

    public function test_chaining(): void
    {
        $payload = (new RawPayload())
            ->set('index', 'test')
            ->set('body.query', ['match_all' => new \stdClass()])
            ->setIfNotEmpty('body.sort', [['_score']])
            ->get();

        $this->assertEquals('test', $payload['index']);
        $this->assertArrayHasKey('query', $payload['body']);
        $this->assertArrayHasKey('sort', $payload['body']);
    }
}
