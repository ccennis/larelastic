<?php

namespace Larelastic\Elastic\Tests;

use PHPUnit\Framework\TestCase;

class AbstractTestCase extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
