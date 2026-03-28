<?php

namespace framework\web\utils;

use Exception;
use framework\Application;

/**
 * @author Faheem Anis
 * 
 * This class compiles a "blade-like" view into standard PHP
 * using standard regex replacements. It supports most blade
 * features. 
 */
class ViewCompiler
{
    protected string $cacheDir;
    protected string $templateDir;

    /**
     * Replacements to be performed on target string
     * 
     * This coverts most of the syntatic sugar
     * added by the engine
     */
    protected $replacements = [];

    protected $layout = null;
    protected $layoutData = null;

    protected $data = [];

    protected $widgets = [];

    public function __construct(string $templateDir, string $cacheDir)
    {
        $this->cacheDir = rtrim($cacheDir, '/');
        $this->templateDir = rtrim($templateDir, '/');

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }

        $this->replacements = [
            // Directive: @if ... @endif
            ['/@if\s*\((.*?)\)/', '<?php if($1): ?>'],
            ['/@endif/', '<?php endif; ?>'],
            // Directive: @foreach ... @endforeach
            ['/@foreach\s*\(([^()]*+(?:\(([^()]*+)\)[^()]*)*)\)/', '<?php foreach($1): ?>'],
            ['/@endforeach/', '<?php endforeach; ?>'],
            ['/@for\s*\(([^()]*+(?:\(([^()]*+)\)[^()]*)*)\)/', '<?php for($1): ?>'],
            ['/@endfor/', '<?php endfor; ?>'],
            // Directive: @{ ... } (PHP code)
            ['/@\{([^{}]+)\}/s', '<?php $1 ?>'],
            // Directive: {{!! ... }} (Unsafe echo)
            ['/\{\{!!([^\n{}]+)\}\}/', '<?= $1 ?>'],
            // Directive: {{ ... }}
            ['/\{\{([^\n{}]+)\}\}/', '<?= htmlspecialchars($1) ?>'],
            // Directive: @layout('view', data)
            ['/@layout\(\s*([\'"])(.*?)\1\s*(?:,\s*(.*?))\)/s', '<?php $this->layout = "$2"; $this->layoutData = $3; ?>'],
            // Directive: @layout('view')
            ['/@layout\(\s*([\'"])(.*?)\1\)/s', '<?php $this->layout = "$2";'],
            // Directive <Namespace.Widget />
            [
                '/<([A-Z][A-Za-z0-9\.]*)\b((?:[^"\'>]|"[^"]*"|\'[^\']*\')*)\/>/',
                function ($matches) {
                    $tagName = $matches[1];
                    // var_export handles escaping single quotes and wrapping the string perfectly
                    $attributes = var_export($matches[2], true);

                    return "<?= \$this->renderWidgetByName('{$tagName}', {$attributes}); ?>";
                }
            ],
            // Directive <Namespace.Widget></Namespace.Widget>
            [
                '/<([A-Z][A-Za-z0-9\.]*)\b((?>"[^"]*"|[^\/>"]*)*)>(.*?)<\/\1>/s',
                function ($matches) {
                    $tagName = $matches[1];
                    // var_export handles escaping single quotes and wrapping the string perfectly
                    $attributes = var_export($matches[2], true);
                    $content = $matches[3];

                    return "<?php \$this->pushWidget('{$tagName}', {$attributes}); ob_start(); ?>{$content}<?= \$this->renderWidget(ob_get_clean()); \$this->popWidget(); ?>";
                },
            ],
        ];
    }

    public function exists($view)
    {
        // Remove escaped dots
        $view = str_replace('\.', '--*--', $view);

        // Convert dots to spaces
        $view = str_replace('.', '/', $view);

        // Convert escaped dots
        $view = str_replace('--*--', '.', $view);

        $templatePath = $this->templateDir . '/' . $view . '.html.php';

        return file_exists($templatePath);
    }

    /**
     * Renders the view.
     */
    public function render(string $view, array $data = []): void
    {
        $this->layout = null;
        $this->layoutData = [];
        $this->data = $data;

        // Remove escaped dots
        $view = str_replace('\.', '--*--', $view);

        // Convert dots to spaces
        $view = str_replace('.', '/', $view);

        // Convert escaped dots
        $view = str_replace('--*--', '.', $view);

        $templatePath = $this->templateDir . '/' . $view . '.html.php';
        $compiledPath = $this->cacheDir . '/' . md5($view) . '.php';

        if (!$this->isCacheValid($templatePath, $compiledPath)) {
            $this->compile($templatePath, $compiledPath);
        }

        // Add core variables to $data
        $data['app'] = Application::get();

        extract($data);
        ob_start();
        include $compiledPath;
        $content = ob_get_clean();

        if (empty($this->layout)) {
            echo $content;
        } else {
            $this->renderLayout($content);
        }
    }

    /**
     * Checks if the compiled file exists and is newer than the source template.
     */
    protected function isCacheValid(string $templatePath, string $compiledPath): bool
    {
        if (!file_exists($compiledPath)) {
            return false;
        }

        return filemtime($compiledPath) >= filemtime($templatePath);
    }

    /**
     * Reads the template, runs regex replacements, and saves the PHP file.
     */
    protected function compile(string $templatePath, string $compiledPath): void
    {
        $content = file_get_contents($templatePath);

        if ($content === false) {
            throw new Exception("Unable to resolve view, \"$templatePath\"");
        }

        foreach ($this->replacements as $replacement) {
            [$pattern, $handler] = $replacement;

            do {
                $old = $content;

                if (is_callable($handler)) {
                    $content = preg_replace_callback($pattern, $handler, $content);
                } else {
                    $content = preg_replace($pattern, $handler, $content);
                }

                // Keep looping as long as this specific replacement is still making changes
            } while ($content !== $old);
        }

        file_put_contents($compiledPath, $content);
    }

    protected function renderLayout($content)
    {
        $this->render($this->layout, array_merge($this->layoutData, [
            'content' => $content
        ]));
    }

    public function pushWidget($widget, string $params = '')
    {
        if (!empty($params)) {
            $params = stripcslashes($params);
            $params = preg_replace('/:([-\w]+)\s*=\s*"([^"]+)"/', '"$1" => $2,', $params);
            $params = preg_replace('/([-\w]+)\s*=\s*"([^"]+)"/', '"$1" => "$2",', $params);
            $params = preg_replace('/:([-\w]+)\b/', '"$1" => true,', $params);

            extract($this->data);

            eval ('$params = [' . $params . '];');
        } else {
            $params = [];
        }
        $widget = app()->widgets->get($widget, $params);
        app()->widgets->begin($widget);
        array_push($this->widgets, $widget);
    }

    protected function renderWidget($content = ''): string
    {
        return app()->widgets->render(end($this->widgets), [], $content);
    }

    protected function renderWidgetByName($name, $params)
    {
        $this->pushWidget($name, $params);
        $result = $this->renderWidget();
        $this->popWidget();

        return $result;
    }

    protected function popWidget()
    {
        app()->widgets->end(array_pop($this->widgets));
    }
}
