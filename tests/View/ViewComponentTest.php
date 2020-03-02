<?php

namespace Illuminate\Tests\View;

use Illuminate\View\Component;
use PHPUnit\Framework\TestCase;

class ViewComponentTest extends TestCase
{
    public function testDataExposure()
    {
        $component = new TestViewComponent;

        $variables = $component->data();

        $this->assertEquals(10, $variables['votes']);
        $this->assertEquals('world', $variables['hello']());
        $this->assertEquals('taylor', $variables['hello']('taylor'));
    }

    public function testPublicMethodsWithNoArgsAreEagerlyInvokedAndNotCached()
    {
        $component = new TestSampleViewComponent;

        $this->assertEquals(0, $component->counter);
        $variables = $component->data();
        $this->assertEquals(1, $component->counter);

        $this->assertEquals('noArgs val', $variables['noArgs']);
        $this->assertEquals(0, $variables['counter']);

        // make sure non-public members are not invoked nor counted.
        $this->assertEquals(1, $component->counter);
        $this->assertArrayHasKey('publicHello', $variables);
        $this->assertArrayNotHasKey('protectedHello', $variables);
        $this->assertArrayNotHasKey('privateHello', $variables);

        $this->assertArrayNotHasKey('protectedCounter', $variables);
        $this->assertArrayNotHasKey('privateCounter', $variables);

        // test each time we invoke data(), the non-argument methods are invoked
        $this->assertEquals(1, $component->counter);
        $component->data();
        $this->assertEquals(2, $component->counter);
        $component->data();
        $this->assertEquals(3, $component->counter);
    }

    public function testItIgnoresExceptedMethodsAndProperties()
    {
        $component = new TestExceptedViewComponent();
        $variables = $component->data();

        // Ignored methods (with no args) are not invoked behind the scenes.
        $this->assertEquals('Otwell', $component->taylor);

        $this->assertArrayNotHasKey('hello', $variables);
        $this->assertArrayNotHasKey('hello2', $variables);
        $this->assertArrayNotHasKey('taylor', $variables);
    }

    public function testMethodsOverridePropertyValues()
    {
        $component = new TestHelloPropertyHelloMethodComponent();
        $variables = $component->data();
        $this->assertArrayHasKey('hello', $variables);
        $this->assertEquals('world', $variables['hello']());

        // protected methods do not override public properties.
        $this->assertArrayHasKey('world', $variables);
        $this->assertEquals('world property', $variables['world']);
    }

    public function testAttributesAreMergedNotOverwritten()
    {
        $component = new TestDefaultAttributesComponent;

        $this->assertEquals('text-red-500', $component->attributes->get('class'));

        $component->withAttributes(['class' => 'bg-blue-100']);

        $this->assertEquals('bg-blue-100 text-red-500', $component->attributes->get('class'));
    }
}

class TestViewComponent extends Component
{
    public $votes = 10;

    public function render()
    {
        return 'test';
    }

    public function hello($string = 'world')
    {
        return $string;
    }
}

class TestSampleViewComponent extends Component
{
    public $counter = 0;

    protected $protectedCounter = 0;

    private $privateCounter = 0;

    public function render()
    {
        return 'test';
    }

    public function publicHello($string = 'world')
    {
        $this->counter = 100;

        return $string;
    }

    public function noArgs()
    {
        $this->counter++;

        return 'noArgs val';
    }

    protected function protectedHello()
    {
        $this->counter++;
    }

    private function privateHello()
    {
        $this->counter++;
    }
}

class TestExceptedViewComponent extends Component
{
    protected $except = ['hello', 'hello2', 'taylor'];

    public $taylor = 'Otwell';

    public function hello($string = 'world')
    {
        return $string;
    }

    public function hello2()
    {
        return $this->taylor = '';
    }

    public function render()
    {
        return 'test';
    }
}

class TestHelloPropertyHelloMethodComponent extends Component
{
    public function render()
    {
        return 'test';
    }

    public $hello = 'hello property';

    public $world = 'world property';

    public function hello($string = 'world')
    {
        return $string;
    }

    protected function world($string = 'world')
    {
        return $string;
    }
}

class TestDefaultAttributesComponent extends Component
{
    public function __construct()
    {
        $this->withAttributes(['class' => 'text-red-500']);
    }

    public function render()
    {
        return $this->attributes->get('id');
    }
}
