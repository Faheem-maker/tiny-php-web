<?php

namespace framework\web\components;

use framework\Application;
use framework\Component;
use RuntimeException;

/**
 * This class provides an easy way to
 * manage assets. This would automatically
 * publish and render the assets as needed.
 */
class AssetManager extends Component
{
    protected string $sourcePath;
    protected string $publicPath;
    protected string $publicUrl;

    protected array $scripts = [];
    protected array $styles = [];

    protected string $hash;

    public function init(): void
    {
        $app = Application::get();

        $this->sourcePath = $app->path->resources();
        $this->publicPath = $app->path->public();
        $this->publicUrl = $app->url->public();

        // Simple version hash (changes when app restarts)
        $this->hash = substr(md5($this->sourcePath), 0, 10);
    }

    /**
     * Publish a file into hashed public directory
     */
    public function publish(string $relativePath): string
    {
        $sourceFile = $this->sourcePath . '/' . ltrim($relativePath, '/');

        if (!file_exists($sourceFile)) {
            throw new RuntimeException("Asset not found: {$sourceFile}");
        }

        $targetDir = $this->publicPath . '/assets/' . $this->hash;
        $targetFile = $targetDir . '/' . $relativePath;

        // Create directory if needed
        if (!is_dir(dirname($targetFile))) {
            mkdir(dirname($targetFile), 0777, true);
        }

        // Copy if not published yet or source file is newer
        if (
            !file_exists($targetFile) ||
            filemtime($sourceFile) > filemtime($targetFile)
        ) {
            copy($sourceFile, $targetFile);
        }

        return $this->publicUrl . '/assets/' . $this->hash . '/' . $relativePath;
    }

    public function addScript(string $relativePath): void
    {
        $url = $this->publish($relativePath);
        $this->scripts[$url] = $url; // prevent duplicates
    }

    public function addCss(string $relativePath): void
    {
        $url = $this->publish($relativePath);
        $this->styles[$url] = $url; // prevent duplicates
    }

    public function renderScripts(): string
    {
        $output = '';

        foreach ($this->scripts as $src) {
            $output .= "<script src=\"{$src}\"></script>\n";
        }

        return $output;
    }

    public function renderCss(): string
    {
        $output = '';

        foreach ($this->styles as $href) {
            $output .= "<link rel=\"stylesheet\" href=\"{$href}\">\n";
        }

        return $output;
    }
}