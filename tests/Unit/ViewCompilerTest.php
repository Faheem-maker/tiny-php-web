<?php

namespace framework\web\tests\Unit;

use framework\web\utils\ViewCompiler;
use PHPUnit\Framework\TestCase;

class ViewCompilerTest extends TestCase
{
    protected ViewCompiler $compiler;

    protected function setUp(): void
    {
        parent::setUp();
        // Use dummy directories for testing compileString
        $templateDir = __DIR__;
        $cacheDir = dirname(__DIR__) . '/runtime/cache';
        
        $this->compiler = new ViewCompiler($templateDir, $cacheDir);
    }

    public function testEcho()
    {
        $template = '{{ $name }}';
        $expected = '<?= htmlspecialchars( $name ) ?>';
        $this->assertEquals($expected, $this->compiler->compileString($template));
    }

    public function testUnsafeEcho()
    {
        $template = '{{!! $name }}';
        $expected = '<?=  $name  ?>';
        $this->assertEquals($expected, $this->compiler->compileString($template));
    }

    public function testIfStatement()
    {
        $template = '@if($user->isAdmin()) Admin @endif';
        $expected = '<?php if($user->isAdmin()): ?> Admin <?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($template));
    }

    public function testForeachLoop()
    {
        $template = '@foreach($users as $user) {{ $user->name }} @endforeach';
        $expected = '<?php foreach($users as $user): ?> <?= htmlspecialchars( $user->name ) ?> <?php endforeach; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($template));
    }

    public function testForLoop()
    {
        $template = '@for($i = 0; $i < 10; $i++) {{ $i }} @endfor';
        $expected = '<?php for($i = 0; $i < 10; $i++): ?> <?= htmlspecialchars( $i ) ?> <?php endfor; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($template));
    }

    public function testPhpBlock()
    {
        $template = '@{ $x = 10; }';
        $expected = '<?php  $x = 10;  ?>';
        $this->assertEquals($expected, $this->compiler->compileString($template));
    }

    public function testWidgetSelfClosing()
    {
        $template = '<Shared.Header title="Main Page" />';
        // matches[2] captures the space and the attributes
        $expected = "<?= \$this->renderWidgetByName('Shared.Header', ' title=\"Main Page\" '); ?>";
        $this->assertEquals($expected, $this->compiler->compileString($template));
    }

    public function testWidgetWithContent()
    {
        $template = '<Shared.Card title="Info">Card Content</Shared.Card>';
        $expected = "<?php \$this->pushWidget('Shared.Card', ' title=\"Info\"'); ob_start(); ?>Card Content<?= \$this->renderWidget(ob_get_clean()); \$this->popWidget(); ?>";
        $this->assertEquals($expected, $this->compiler->compileString($template));
    }

    public function testLayout()
    {
        $template = "@layout('main')";
        $expected = "<?php \$this->layout = \"main\"; ?>";
        $this->assertEquals($expected, $this->compiler->compileString($template));
    }

    public function testLayoutWithData()
    {
        $template = "@layout('main', ['title' => 'Home'])";
        $expected = "<?php \$this->layout = \"main\"; \$this->layoutData = ['title' => 'Home']; ?>";
        $this->assertEquals($expected, $this->compiler->compileString($template));
    }
}
