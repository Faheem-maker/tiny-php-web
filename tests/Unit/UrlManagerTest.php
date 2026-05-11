<?php

namespace framework\web\tests\Unit;

use framework\web\Route;
use framework\web\Routes;
use framework\web\WebApplication;
use PHPUnit\Framework\TestCase;

class UrlManagerTest extends TestCase
{
    /* -----------------------------------------------------------------
     |  Current URL inspection tests
     | -----------------------------------------------------------------
     */

    public function testFullReturnsCompleteUrl()
    {
        $app = createApp([
            'app' => ['base_url' => 'http://example.com']
        ]);

        $this->assertEquals('http://example.com/', $app->url->full());
    }

    public function testFullWithSubfolder()
    {
        $app = WebApplication::getInstance('/page', 'GET');
        $app->registerComponent('config', new \framework\components\Config());
        $app->registerComponent('path', new \framework\components\PathManager());
        $app->registerComponent('url', new \framework\web\components\UrlManager());
        
        $app->config->set('app', ['base_url' => 'http://example.com/subfolder']);
        $app->config->set('paths', [
            'base_dir' => __DIR__,
            'root' => __DIR__,
            'runtime' => __DIR__ . '/runtime',
        ]);
        
        $app->init();

        $this->assertEquals('http://example.com/subfolder/page', $app->url->full());
    }

    public function testPathReturnsNormalizedRequestPath()
    {
        $app = createApp();
        $this->assertEquals('/', $app->url->path());
    }

    public function testPathWithComplexRoute()
    {
        $app = WebApplication::getInstance('/users/123/edit', 'GET');
        $app->registerComponent('config', new \framework\components\Config());
        $app->registerComponent('path', new \framework\components\PathManager());
        $app->registerComponent('url', new \framework\web\components\UrlManager());
        
        $app->config->set('app', ['base_url' => '/']);
        $app->config->set('paths', [
            'base_dir' => __DIR__,
            'root' => __DIR__,
            'runtime' => __DIR__ . '/runtime',
        ]);
        
        $app->init();

        $this->assertEquals('/users/123/edit', $app->url->path());
    }

    public function testJoinCombinesSegments()
    {
        $app = createApp();
        
        $result = $app->url->join('http://example.com', 'api', 'v1', 'users');
        $this->assertEquals('http://example.com/api/v1/users', $result);
    }

    public function testJoinHandlesTrailingSlashes()
    {
        $app = createApp();
        
        $result = $app->url->join('http://example.com/', '/api/', '/users/');
        $this->assertEquals('http://example.com/api/users', $result);
    }

    public function testJoinWithSingleSegment()
    {
        $app = createApp();
        
        $result = $app->url->join('http://example.com');
        $this->assertEquals('http://example.com', $result);
    }

    /* -----------------------------------------------------------------
     |  Base URL handling tests
     | -----------------------------------------------------------------
     */

    public function testBaseReturnsBaseUrl()
    {
        $app = createApp([
            'app' => ['base_url' => 'http://example.com']
        ]);

        $this->assertEquals('http://example.com', $app->url->base());
    }

    public function testBaseWithTrailingSlash()
    {
        $app = createApp([
            'app' => ['base_url' => 'http://example.com/']
        ]);

        $this->assertEquals('http://example.com', $app->url->base());
    }

    public function testBaseWithSubfolder()
    {
        $app = createApp([
            'app' => ['base_url' => 'http://example.com/subfolder']
        ]);

        $this->assertEquals('http://example.com/subfolder', $app->url->base());
    }

    public function testBasePathReturnsBaseUrl()
    {
        $app = createApp([
            'app' => ['base_url' => 'http://example.com/app']
        ]);

        $this->assertEquals('http://example.com/app', $app->url->basePath());
    }

    public function testIsSecureReturnsFalseByDefault()
    {
        $app = createApp();
        
        $this->assertFalse($app->url->isSecure());
    }

    public function testIsSecureReturnsTrueWhenHttpsSet()
    {
        $_SERVER['HTTPS'] = 'on';
        $app = createApp();
        
        $this->assertTrue($app->url->isSecure());
        
        unset($_SERVER['HTTPS']);
    }

    /* -----------------------------------------------------------------
     |  Path normalization tests
     | -----------------------------------------------------------------
     */

    public function testNormalizeAddsLeadingSlash()
    {
        $app = createApp();
        
        $this->assertEquals('/users', $app->url->normalize('users'));
    }

    public function testNormalizeRemovesTrailingSlash()
    {
        $app = createApp();
        
        $this->assertEquals('/users', $app->url->normalize('/users/'));
    }

    public function testNormalizeKeepsRootSlash()
    {
        $app = createApp();
        
        $this->assertEquals('/', $app->url->normalize('/'));
    }

    public function testNormalizeHandlesDotSegments()
    {
        $app = createApp();
        
        $this->assertEquals('/users', $app->url->normalize('/users/./profile/..'));
    }

    public function testNormalizeHandlesDoubleDots()
    {
        $app = createApp();
        
        $this->assertEquals('/api', $app->url->normalize('/api/v1/../v2/..'));
    }

    public function testNormalizeHandlesMultipleSlashes()
    {
        $app = createApp();
        
        $this->assertEquals('/users/profile', $app->url->normalize('//users///profile//'));
    }

    public function testNormalizeComplexPath()
    {
        $app = createApp();
        
        $this->assertEquals('/api/users', $app->url->normalize('/api/./v1/../users/'));
    }

    /* -----------------------------------------------------------------
     |  URL generation tests
     | -----------------------------------------------------------------
     */

    public function testToGeneratesSimpleUrl()
    {
        $app = createApp();
        
        $this->assertEquals('/users', $app->url->to('users'));
    }

    public function testToGeneratesUrlWithLeadingSlash()
    {
        $app = createApp();
        
        $this->assertEquals('/users', $app->url->to('/users'));
    }

    public function testToGeneratesRootUrl()
    {
        $app = createApp();
        
        $this->assertEquals('/', $app->url->to('/'));
    }

    public function testToWithBaseUrl()
    {
        $app = createApp([
            'app' => ['base_url' => 'http://example.com']
        ]);
        
        $this->assertEquals('http://example.com/users', $app->url->to('users'));
    }

    public function testToWithSubfolderBase()
    {
        $app = createApp([
            'app' => ['base_url' => 'http://example.com/app']
        ]);
        
        $this->assertEquals('http://example.com/app/users', $app->url->to('users'));
    }

    public function testToWithQueryParameters()
    {
        $app = createApp();
        
        $url = $app->url->to('users', [], ['page' => 2, 'limit' => 10]);
        $this->assertEquals('/users?page=2&limit=10', $url);
    }

    public function testToWithPlaceholderAndArray()
    {
        $app = createApp();
        
        $url = $app->url->to('users/{id}', ['id' => 123]);
        $this->assertEquals('/users/123', $url);
    }

    public function testToWithMultiplePlaceholders()
    {
        $app = createApp();
        
        $url = $app->url->to('users/{userId}/posts/{postId}', [
            'userId' => 123,
            'postId' => 456
        ]);
        $this->assertEquals('/users/123/posts/456', $url);
    }

    public function testToWithIndexedArrayParams()
    {
        $app = createApp();
        
        $url = $app->url->to('users/{id}/posts/{postId}', [123, 456]);
        $this->assertEquals('/users/123/posts/456', $url);
    }

    public function testToWithMixedParams()
    {
        $app = createApp();
        
        $url = $app->url->to('users/{id}', ['id' => 123], ['tab' => 'profile']);
        $this->assertEquals('/users/123?tab=profile', $url);
    }

    public function testToWithUnusedParams()
    {
        $app = createApp();
        
        $url = $app->url->to('users', ['id' => 123], ['page' => 1]);
        $this->assertEquals('/users?id=123&page=1', $url);
    }

    /* -----------------------------------------------------------------
     |  Named route tests
     | -----------------------------------------------------------------
     */

    public function testNamedGeneratesUrlFromRouteName()
    {
        $app = createApp();
        
        // Register a named route
        $route = new Route(function() {}, '/users/{id}', 'user.show');
        Routes::name('user.show', $route);
        
        $url = $app->url->named('user.show', ['id' => 123]);
        $this->assertEquals('/users/123', $url);
    }

    public function testNamedWithBaseUrl()
    {
        $app = createApp([
            'app' => ['base_url' => 'http://example.com']
        ]);
        
        $route = new Route(function() {}, '/users/{id}', 'user.show');
        Routes::name('user.show', $route);
        
        $url = $app->url->named('user.show', ['id' => 123]);
        $this->assertEquals('http://example.com/users/123', $url);
    }

    public function testByNameGeneratesRelativeUrl()
    {
        $app = createApp();
        
        $route = new Route(function() {}, '/users/{id}', 'user.show');
        Routes::name('user.show', $route);
        
        $url = $app->url->byName('user.show', ['id' => 123]);
        $this->assertEquals('/users/123', $url);
    }

    public function testByNameWithQueryParams()
    {
        $app = createApp();
        
        $route = new Route(function() {}, '/users/{id}', 'user.show');
        Routes::name('user.show', $route);
        
        $url = $app->url->byName('user.show', ['id' => 123, 'tab' => 'profile']);
        $this->assertEquals('/users/123?tab=profile', $url);
    }

    public function testByNameWithIndexedParams()
    {
        $app = createApp();
        
        $route = new Route(function() {}, '/users/{id}/posts/{postId}', 'user.post');
        Routes::name('user.post', $route);
        
        $url = $app->url->byName('user.post', [123, 456]);
        $this->assertEquals('/users/123/posts/456', $url);
    }

    /* -----------------------------------------------------------------
     |  Edge cases and special scenarios
     | -----------------------------------------------------------------
     */

    public function testNormalizeEmptyString()
    {
        $app = createApp();
        
        $this->assertEquals('/', $app->url->normalize(''));
    }

    public function testToWithEmptyPath()
    {
        $app = createApp();
        
        $this->assertEquals('/', $app->url->to(''));
    }

    public function testJoinWithEmptySegments()
    {
        $app = createApp();
        
        $result = $app->url->join('http://example.com', '', 'users');
        $this->assertEquals('http://example.com//users', $result);
    }

    public function testToWithSpecialCharactersInQuery()
    {
        $app = createApp();
        
        $url = $app->url->to('search', [], ['q' => 'hello world', 'filter' => 'a&b']);
        $this->assertStringContainsString('search?', $url);
        $this->assertStringContainsString('q=hello+world', $url);
    }

    public function testNormalizeWithOnlyDots()
    {
        $app = createApp();
        
        $this->assertEquals('/', $app->url->normalize('/.'));
        $this->assertEquals('/', $app->url->normalize('/..'));
    }

    public function testToWithTrailingSlashInPath()
    {
        $app = createApp();
        
        $this->assertEquals('/users', $app->url->to('users/'));
    }

    public function testBaseUrlWithRootOnly()
    {
        $app = createApp([
            'app' => ['base_url' => '/']
        ]);
        
        $this->assertEquals('/', $app->url->base());
        $this->assertEquals('/users', $app->url->to('users'));
    }

    public function testFullUrlWithRootBase()
    {
        $app = createApp([
            'app' => ['base_url' => '/']
        ]);
        
        $this->assertEquals('/', $app->url->full());
    }

    public function testNormalizePreservesQueryString()
    {
        $app = createApp();
        
        // normalize should only work on path, not query strings
        // but the method doesn't handle query strings, so this tests current behavior
        $result = $app->url->normalize('/users?page=1');
        $this->assertEquals('/users?page=1', $result);
    }

    public function testToWithMultipleConsecutivePlaceholders()
    {
        $app = createApp();
        
        $url = $app->url->to('api/{version}/{resource}', ['v1', 'users']);
        $this->assertEquals('/api/v1/users', $url);
    }

    public function testPublicReturnsEmptyString()
    {
        $app = createApp();
        
        $this->assertEquals('', $app->url->public());
    }

    public function tearDown(): void {
        parent::tearDown();

        WebApplication::flushInstance();
    }
}
