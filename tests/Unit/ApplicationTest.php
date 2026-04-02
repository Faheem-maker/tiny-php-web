<?php

namespace framework\web\tests\Unit;

use framework\web\WebApplication;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    public function testApplicationInstance()
    {
        $app = createApp();

        $this->assertInstanceOf(WebApplication::class, $app);
    }
}