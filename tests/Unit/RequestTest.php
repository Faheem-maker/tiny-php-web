<?php

namespace framework\web\tests\Unit;

use framework\web\request\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    protected $backupGet;
    protected $backupPost;
    protected $backupServer;

    protected function setUp(): void
    {
        $this->backupGet = $_GET;
        $this->backupPost = $_POST;
        $this->backupServer = $_SERVER;

        $_GET = [];
        $_POST = [];
        $_SERVER = [];
    }

    protected function tearDown(): void
    {
        $_GET = $this->backupGet;
        $_POST = $this->backupPost;
        $_SERVER = $this->backupServer;
    }

    public function testDefaultGet()
    {
        $_GET = ['name' => 'John', 'age' => 30];
        $request = new Request();

        $this->assertEquals($_GET, $request->get());
    }

    public function testSpecificGet()
    {
        $_GET = ['name' => 'John'];
        $request = new Request();

        $this->assertEquals('John', $request->get('name'));
        $this->assertEquals('Doe', $request->get('last_name', 'Doe'));
    }

    public function testDefaultPost()
    {
        $_POST = ['email' => 'john@example.com'];
        $request = new Request();

        $this->assertEquals('john@example.com', $request->post('email'));
        $this->assertNull($request->post('password'));
    }

    public function testDefaultInput()
    {
        $_GET = ['source' => 'google', 'campaign' => 'summer'];
        $_POST = ['campaign' => 'winter', 'user' => 'admin'];

        $request = new Request();

        // Specific keys
        $this->assertEquals('google', $request->input('source'));
        $this->assertEquals('winter', $request->input('campaign')); // POST takes precedence
        $this->assertEquals('admin', $request->input('user'));

        // All inputs
        $all = $request->input();
        $this->assertEquals('winter', $all['campaign']);
        $this->assertEquals('google', $all['source']);
        $this->assertEquals('admin', $all['user']);
    }

    public function testHttpMethod()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request = new Request();
        $this->assertEquals('POST', $request->method());

        $_GET['__method'] = 'PUT';
        $this->assertEquals('PUT', $request->method());
    }

    public function testPut()
    {
        $request = new Request();
        $request->put('is_auth', true);
        $request->put('user', ['id' => 1]);

        $this->assertTrue($request->is_auth);
        $this->assertEquals(['id' => 1], $request->user);
    }
}
