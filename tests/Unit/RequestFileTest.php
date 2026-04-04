<?php

namespace framework\web\tests\Unit;

use framework\web\request\Request;
use framework\web\request\UploadedFile;
use PHPUnit\Framework\TestCase;

class RequestFileTest extends TestCase
{
    protected $backupFiles;

    protected function setUp(): void
    {
        $this->backupFiles = $_FILES;
        $_FILES = [];
    }

    protected function tearDown(): void
    {
        $_FILES = $this->backupFiles;
    }

    public function testSingleFileUpload()
    {
        $_FILES['avatar'] = [
            'name' => 'avatar.png',
            'type' => 'image/png',
            'tmp_name' => '/tmp/php123',
            'error' => 0,
            'size' => 100,
        ];

        $request = new Request();
        $file = $request->files('avatar');

        $this->assertInstanceOf(UploadedFile::class, $file);
        $this->assertEquals('avatar.png', $file->name);
    }

    public function testMultipleFileUpload()
    {
        $_FILES['docs'] = [
            'name' => ['file1.txt', 'file2.txt'],
            'type' => ['text/plain', 'text/plain'],
            'tmp_name' => ['/tmp/php1', '/tmp/php2'],
            'error' => [0, 0],
            'size' => [123, 456],
        ];

        $request = new Request();
        $files = $request->files('docs');

        $this->assertIsArray($files);
        $this->assertCount(2, $files);
        $this->assertInstanceOf(UploadedFile::class, $files[0]);
        $this->assertEquals('file1.txt', $files[0]->name);
        $this->assertEquals('file2.txt', $files[1]->name);
    }

    public function testNestedFileUpload()
    {
        $_FILES['user'] = [
            'name' => ['avatar' => 'me.png'],
            'type' => ['avatar' => 'image/png'],
            'tmp_name' => ['avatar' => '/tmp/php456'],
            'error' => ['avatar' => 0],
            'size' => ['avatar' => 500],
        ];

        $request = new Request();
        $files = $request->files('user');

        $this->assertIsArray($files);
        $this->assertInstanceOf(UploadedFile::class, $files['avatar']);
        $this->assertEquals('me.png', $files['avatar']->name);
    }

    public function testDotNotationAccess()
    {
        $_FILES['user'] = [
            'name' => ['profile' => ['avatar' => 'me.png']],
            'type' => ['profile' => ['avatar' => 'image/png']],
            'tmp_name' => ['profile' => ['avatar' => '/tmp/php456']],
            'error' => ['profile' => ['avatar' => 0]],
            'size' => ['profile' => ['avatar' => 500]],
        ];

        $request = new Request();
        $file = $request->files('user.profile.avatar');

        $this->assertInstanceOf(UploadedFile::class, $file);
        $this->assertEquals('me.png', $file->name);
    }
}
