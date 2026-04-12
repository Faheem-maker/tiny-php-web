<?php

namespace framework\web\components;

use framework\Application;
use framework\Component;
use framework\web\Routes;
use framework\db\ActiveModel;

/**
 * URL Manager
 *
 * Responsible for:
 *  - Parsing the current request URL
 *  - Normalizing paths
 *  - Generating application URLs
 *  - Working with named routes
 *  - Assisting redirects and navigation helpers
 */
class UrlManager extends Component
{
    /**
     * Base URL (scheme + host + optional subfolder)
     */
    protected ?string $baseUrl = null;

    /**
     * Current request path
     */
    protected ?string $currentPath = null;

    /**
     * Constructor
     */
    public function init(): void
    {
        $app = Application::get();

        $this->baseUrl = config('app.base_url');
        $this->currentPath = $this->normalize($this->removeBase($app->route));

    }

    /* -----------------------------------------------------------------
     |  Current URL inspection
     | -----------------------------------------------------------------
     */

    /**
     * Get full current URL (absolute)
     */
    public function full(): string
    {
        return $this->join($this->base(), $this->currentPath);
    }

    /**
     * Get normalized request path
     */
    public function path(): string
    {
        return $this->currentPath;
    }

    public function join(...$segments): string
    {
        $result = '';

        foreach ($segments as $segment) {
            $result .= rtrim($segment, '/') . '/';
        }

        return substr($result, 0, strlen($result) - 1);
    }

    /* -----------------------------------------------------------------
     |  Base URL handling
     | -----------------------------------------------------------------
     */

    /**
     * Get base URL (scheme + host + base path)
     */
    public function base(): string
    {
        return $this->trimTrailingSlash($this->baseUrl);
    }

    public function public(): string
    {
        return $this->to('/public');
    }

    /**
     * Get base path only (subfolder install support)
     */
    public function basePath(): string
    {
        return $this->baseUrl;
    }

    /**
     * Detect if current request is HTTPS
     */
    public function isSecure(): bool
    {
        return !empty($_SERVER['HTTPS']);
    }

    /* -----------------------------------------------------------------
     |  Path normalization
     | -----------------------------------------------------------------
     */

    /**
     * Normalize a path (remove duplicate slashes, resolve dots, etc.)
     * 
     * This method ensures a leading slash, removes trailing slashes (except for root), and resolves any '.' or '..' segments in the path. It also removes any duplicate slashes.
     */
    public function normalize(string $path): string
    {
        // Ensure leading slash
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        // Remove trailing slash except for root
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        // Split into segments and process
        $segments = explode('/', $path);
        $normalized = [];
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            } elseif ($segment === '..') {
                array_pop($normalized);
            } else {
                $normalized[] = $segment;
            }
        }

        // Rejoin
        $result = '/' . implode('/', $normalized);

        // Ensure root if empty
        return $result === '/' ? '/' : $result;
    }

    /**
     * Ensure leading slash exists
     */
    protected function ensureLeadingSlash(string $path): string
    {
        return rtrim($path, '/') . '/';
    }

    /**
     * Remove trailing slash (except root)
     */
    protected function trimTrailingSlash(string $path): string
    {
        return rtrim($path, '/') ?: '/';
    }

    /**
     * Removes the base URL from the start of
     * given path
     */
    protected function removeBase(string $path): string
    {
        if (str_starts_with($path, $this->baseUrl)) {
            return substr($path, strlen($this->baseUrl));
        }

        return $path;
    }

    /* -----------------------------------------------------------------
     |  URL generation
     | -----------------------------------------------------------------
     */

    /**
     * Generate URL to a path
     *
     * @param string $path
     * @param array  $query
     * @param bool   $absolute
     */
    public function to(string $path = '/', mixed $params = [], array $query = [], bool $absolute = false): string
    {
        [$path, $params] = $this->resolveParameters($path, $params);
        $query = array_merge($params, $query);

        if ($absolute) {
            return $path;
        }
        return ($this->base() == '/' ? '' : $this->base()) . '/' . trim($path, '/') . ($query ? '?' . http_build_query($query) : '');
    }

    public function named(string $name, mixed $params = [])
    {
        return $this->to(Routes::resolveName($name)->fullPath, $params);
    }

    /**
     * Generate relative URL by route name without base URL
     */
    public function byName(string $name, mixed $params = [])
    {
        [$path, $params] = $this->resolveParameters(Routes::resolveName($name)->fullPath, $params);
        return $path . ($params ? '?' . http_build_query($params) : '');
    }

    /**
     * Resolve parameters into path placeholders
     */
    protected function resolveParameters(string $path, mixed $params = []): array
    {
        if ($params instanceof ActiveModel) {
            $pk = $params::primaryKey();
            if ($pk) {
                $path = preg_replace('/\{[a-zA-Z0-9_-]+\}/', $params->$pk, $path, 1);
            }
            return [$path, []];
        }

        if (is_array($params) && !empty($params)) {
            foreach ($params as $key => $value) {
                if ($value instanceof ActiveModel) {
                    $pk = $value::primaryKey();
                    $value = $pk ? $value->$pk : $value;
                }

                if (is_string($key)) {
                    $placeholder = "{" . $key . "}";
                    if (str_contains($path, $placeholder)) {
                        $path = str_replace($placeholder, $value, $path);
                        unset($params[$key]);
                    }
                } else {
                    $count = 0;
                    $path = preg_replace('/\{[a-zA-Z0-9_-]+\}/', (string) $value, $path, 1, $count);
                    if ($count > 0) {
                        unset($params[$key]);
                    }
                }
            }
        }

        return [$path, $params];
    }

    /**
     * Append query parameters to URL
     */
    protected function buildQuery(array $query): string
    {
        // TODO: Implement
    }

    /* -----------------------------------------------------------------
     |  Navigation helpers
     | -----------------------------------------------------------------
     */

    /**
     * Check if current path matches pattern
     */
    public function is(string $pattern): bool
    {
        // TODO: Implement
    }

    /**
     * Check if path starts with prefix
     */
    public function startsWith(string $prefix): bool
    {
        // TODO: Implement
    }

    /**
     * Generate URL with modified query parameters
     */
    public function withQuery(array $query): string
    {
        // TODO: Implement
    }

    /**
     * Remove query parameter(s) from URL
     */
    public function withoutQuery(string|array $keys): string
    {
        // TODO: Implement
    }

    /* -----------------------------------------------------------------
     |  Redirect helpers
     | -----------------------------------------------------------------
     */

    /**
     * Redirect back to referrer
     */
    public function back(int $status = 302): void
    {
        // TODO: Implement
    }
}