<?php

namespace framework\web\tests\Unit\request;

use framework\web\request\UploadedFile;
use PHPUnit\Framework\TestCase;

class UploadedFileTest extends TestCase
{
    protected UploadedFile $file;

    public function setUp(): void
    {
        $this->file = new UploadedFile([
            'name' => 'test.txt',
            'type' => 'text/plain',
            'size' => 100,
            'tmp_name' => '/tmp/test.txt',
            'error' => UPLOAD_ERR_OK,
        ]);
    }

    public function testInstantiate()
    {
        $this->assertInstanceOf(UploadedFile::class, $this->file);
    }

    public function testExtension()
    {
        $this->assertEquals('txt', $this->file->ext());

        $file = new UploadedFile([
            'name' => 'DockerFile',
            'type' => 'text/plain',
            'size' => 100,
            'tmp_name' => '/tmp/DockerFile',
            'error' => UPLOAD_ERR_OK,
        ]);

        $this->assertEquals('', $file->ext());
    }

    public function testError()
    {
        $this->assertTrue($this->file->isValid());

        $file = new UploadedFile([
            'name' => 'DockerFile',
            'type' => 'text/plain',
            'size' => 100,
            'tmp_name' => '/tmp/DockerFile',
            'error' => UPLOAD_ERR_INI_SIZE,
        ]);

        $this->assertFalse($file->isValid());
        $this->assertEquals(UPLOAD_ERR_INI_SIZE, $file->error);
    }

    public function testProperty()
    {
        $this->assertEquals('test.txt', $this->file->name);
        $this->assertEquals('text/plain', $this->file->type);
        $this->assertEquals(100, $this->file->size);
        $this->assertEquals('/tmp/test.txt', $this->file->tmp_name);
        $this->assertEquals(UPLOAD_ERR_OK, $this->file->error);
    }

    public function testMove()
    {
        $app = createApp();
        $result = $this->file->move('/uploads/');

        $this->assertEquals(1, count($app->fs->moved));
        \defined('DS') || define('DS', DIRECTORY_SEPARATOR);
        $expectedPath = realpath(__DIR__ . '/../..') . DS . 'storage' . DS . 'uploads' . DS . 'test.txt';

        $this->assertEquals([
            [
                '/tmp/test.txt',
                $expectedPath
            ]
        ], $app->fs->moved);

        $this->assertEquals([
            'name' => 'test.txt',
            'path' => $expectedPath,
            'original' => 'test.txt'
        ], $result);
    }
}