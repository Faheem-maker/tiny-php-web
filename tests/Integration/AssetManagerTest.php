<?php

namespace framework\web\tests\Integration;

use PHPUnit\Framework\TestCase;
use RuntimeException;

class AssetManagerTest extends TestCase
{
    protected $app;
    protected string $publicPath;
    protected string $assetsPath;

    protected function setUp(): void
    {
        // Create app with custom paths for testing
        $this->assetsPath = __DIR__ . '/../assets';
        $this->publicPath = __DIR__ . '/../public';

        $this->app = createApp([
            'paths.base_dir' => __DIR__ . '/..',
            'paths.root' => __DIR__ . '/..',
            'paths.runtime' => __DIR__ . '/../runtime',
            'paths.assets' => $this->assetsPath,
            'paths.storage' => __DIR__ . '/../storage',
            'paths.public' => $this->publicPath,
            'url.public' => '/public'
        ]);

        // Ensure public directory exists
        if (!is_dir($this->publicPath)) {
            mkdir($this->publicPath, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up the public directory after each test
        $this->removeDirectory($this->publicPath);
    }

    /**
     * Recursively remove a directory and its contents
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($items as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }

    public function testPublishCreatesHashedDirectory()
    {
        $url = $this->app->assets->publish('css/style.css');

        // Verify the file was published to a hashed directory
        $this->assertStringContainsString('/assets/', $url);
        $this->assertStringContainsString('/css/style.css', $url);
        
        // Extract hash from URL and verify file exists
        preg_match('#/assets/([^/]+)/#', $url, $matches);
        $hash = $matches[1];
        
        $publishedFile = $this->publicPath . '/assets/' . $hash . '/css/style.css';
        $this->assertFileExists($publishedFile);
    }

    public function testPublishReturnsCorrectUrl()
    {
        $url = $this->app->assets->publish('js/app.js');

        $this->assertStringStartsWith('/assets/', $url);
        $this->assertStringEndsWith('/js/app.js', $url);
    }

    public function testPublishThrowsExceptionForNonExistentFile()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Asset not found');

        $this->app->assets->publish('non-existent.css');
    }

    public function testPublishUpdatesFileWhenSourceIsNewer()
    {
        // First publish
        $url = $this->app->assets->publish('css/style.css');
        
        preg_match('#/assets/([^/]+)/#', $url, $matches);
        $hash = $matches[1];
        $publishedFile = $this->publicPath . '/assets/' . $hash . '/css/style.css';
        
        // Get initial modification time
        $initialMtime = filemtime($publishedFile);
        
        // Wait a moment and touch the source file to make it newer
        sleep(1);
        touch($this->assetsPath . '/css/style.css');
        
        // Publish again
        $this->app->assets->publish('css/style.css');
        
        // Verify the published file was updated
        $newMtime = filemtime($publishedFile);
        $this->assertGreaterThan($initialMtime, $newMtime);
    }

    public function testPublishDoesNotCopyIfFileAlreadyPublished()
    {
        // First publish
        $url = $this->app->assets->publish('css/style.css');
        
        preg_match('#/assets/([^/]+)/#', $url, $matches);
        $hash = $matches[1];
        $publishedFile = $this->publicPath . '/assets/' . $hash . '/css/style.css';
        
        // Get modification time
        $mtime = filemtime($publishedFile);
        
        // Publish again without changing source
        sleep(1);
        $this->app->assets->publish('css/style.css');
        
        // Verify the file was not re-copied (mtime unchanged)
        $this->assertEquals($mtime, filemtime($publishedFile));
    }

    public function testAddScriptPublishesAndAddsToList()
    {
        $this->app->assets->addScript('js/app.js');

        // Verify file was published
        preg_match('#/assets/([^/]+)/#', $this->app->assets->renderScripts(), $matches);
        $hash = $matches[1];
        $publishedFile = $this->publicPath . '/assets/' . $hash . '/js/app.js';
        
        $this->assertFileExists($publishedFile);
    }

    public function testAddCssPublishesAndAddsToList()
    {
        $this->app->assets->addCss('css/style.css');

        // Verify file was published
        preg_match('#/assets/([^/]+)/#', $this->app->assets->renderCss(), $matches);
        $hash = $matches[1];
        $publishedFile = $this->publicPath . '/assets/' . $hash . '/css/style.css';
        
        $this->assertFileExists($publishedFile);
    }

    public function testRenderScriptsOutputsCorrectHtml()
    {
        $this->app->assets->addScript('js/app.js');

        $output = $this->app->assets->renderScripts();

        $this->assertStringContainsString('<script src="', $output);
        $this->assertStringContainsString('/js/app.js"></script>', $output);
    }

    public function testRenderCssOutputsCorrectHtml()
    {
        $this->app->assets->addCss('css/style.css');

        $output = $this->app->assets->renderCss();

        $this->assertStringContainsString('<link rel="stylesheet" href="', $output);
        $this->assertStringContainsString('/css/style.css">', $output);
    }

    public function testDuplicateScriptsAreNotAddedTwice()
    {
        $this->app->assets->addScript('js/app.js');
        $this->app->assets->addScript('js/app.js');

        $output = $this->app->assets->renderScripts();

        // Count occurrences of the script tag
        $count = substr_count($output, 'js/app.js');
        $this->assertEquals(1, $count, 'Script should only appear once');
    }

    public function testDuplicateCssAreNotAddedTwice()
    {
        $this->app->assets->addCss('css/style.css');
        $this->app->assets->addCss('css/style.css');

        $output = $this->app->assets->renderCss();

        // Count occurrences of the link tag
        $count = substr_count($output, 'css/style.css');
        $this->assertEquals(1, $count, 'CSS should only appear once');
    }

    public function testMultipleScriptsAreRenderedCorrectly()
    {
        $this->app->assets->addScript('js/app.js');
        $this->app->assets->addScript('css/style.css'); // Intentionally wrong extension to test multiple

        $output = $this->app->assets->renderScripts();

        $this->assertStringContainsString('js/app.js', $output);
        $this->assertStringContainsString('css/style.css', $output);
        
        // Verify both script tags are present
        $scriptCount = substr_count($output, '<script src=');
        $this->assertEquals(2, $scriptCount);
    }

    public function testMultipleCssAreRenderedCorrectly()
    {
        $this->app->assets->addCss('css/style.css');
        $this->app->assets->addCss('nested/deep/nested.css');

        $output = $this->app->assets->renderCss();

        $this->assertStringContainsString('css/style.css', $output);
        $this->assertStringContainsString('nested/deep/nested.css', $output);
        
        // Verify both link tags are present
        $linkCount = substr_count($output, '<link rel="stylesheet"');
        $this->assertEquals(2, $linkCount);
    }

    public function testNestedDirectoryAssetsArePublishedCorrectly()
    {
        $url = $this->app->assets->publish('nested/deep/nested.css');

        // Verify URL contains the nested path
        $this->assertStringContainsString('/nested/deep/nested.css', $url);
        
        // Verify file was published with correct directory structure
        preg_match('#/assets/([^/]+)/#', $url, $matches);
        $hash = $matches[1];
        $publishedFile = $this->publicPath . '/assets/' . $hash . '/nested/deep/nested.css';
        
        $this->assertFileExists($publishedFile);
        
        // Verify content is correct
        $content = file_get_contents($publishedFile);
        $this->assertStringContainsString('nested-component', $content);
    }

    public function testRenderScriptsReturnsEmptyStringWhenNoScripts()
    {
        $output = $this->app->assets->renderScripts();

        $this->assertEquals('', $output);
    }

    public function testRenderCssReturnsEmptyStringWhenNoCss()
    {
        $output = $this->app->assets->renderCss();

        $this->assertEquals('', $output);
    }

    public function testPublishWithLeadingSlashInPath()
    {
        $url = $this->app->assets->publish('/css/style.css');

        $this->assertStringContainsString('/css/style.css', $url);
        
        preg_match('#/assets/([^/]+)/#', $url, $matches);
        $hash = $matches[1];
        $publishedFile = $this->publicPath . '/assets/' . $hash . '/css/style.css';
        
        $this->assertFileExists($publishedFile);
    }
}

// Made with Bob
