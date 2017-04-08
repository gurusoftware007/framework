<?php

namespace Illuminate\Tests\Foundation;

use Exception;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Config\Repository as Config;
use Illuminate\Foundation\Exceptions\Handler;

class FoundationExceptionsHandlerTest extends TestCase
{
    protected $config;

    protected $container;

    protected $handler;

    protected $rquest;

    public function setUp()
    {
        $this->config = m::mock(Config::class);

        $this->request = m::mock('StdClass');

        $this->container = Container::setInstance(new Container);

        $this->container->singleton('config', function () {
            return $this->config;
        });

        $this->container->singleton('Illuminate\Contracts\Routing\ResponseFactory', function () {
            return new \Illuminate\Routing\ResponseFactory(
                m::mock(\Illuminate\Contracts\View\Factory::class),
                m::mock(\Illuminate\Routing\Redirector::class)
            );
        });

        $this->handler = new Handler($this->container);
    }

    public function tearDown()
    {
        m::close();
    }

    public function testReturnsHtmlPageWithStackTraceWhenHtmlRequestAndDebugTrue()
    {
        $this->config->shouldReceive('get')->with('app.debug', null)->twice()->andReturn(true);
        $this->request->shouldReceive('expectsJson')->once()->andReturn(false);

        $response = $this->handler->render($this->request, new Exception('My custom error message'))->getContent();

        $this->assertContains('<!DOCTYPE html>', $response);
        $this->assertContains('<h1>Whoops, looks like something went wrong.</h1>', $response);
        $this->assertContains('My custom error message', $response);
        $this->assertContains('::main()', $response);
    }

    public function testReturnsJsonWithStackTraceWhenAjaxRequestAndDebugTrue()
    {
        $this->config->shouldReceive('get')->with('app.debug', null)->once()->andReturn(true);
        $this->request->shouldReceive('expectsJson')->once()->andReturn(true);

        $response = $this->handler->render($this->request, new Exception('My custom error message'))->getContent();

        $this->assertNotContains('<!DOCTYPE html>', $response);
        $this->assertContains('"message": "My custom error message"', $response);
        $this->assertContains('"file"', $response);
        $this->assertContains('"line"', $response);
        $this->assertContains('"trace"', $response);
    }

    public function testReturnsJsonWithoutStackTraceWhenAjaxRequestAndDebugFalse()
    {
        $this->config->shouldReceive('get')->with('app.debug', null)->once()->andReturn(false);
        $this->request->shouldReceive('expectsJson')->once()->andReturn(true);

        $response = $this->handler->render($this->request, new Exception('This error message should not be visible'))->getContent();

        $this->assertContains('"message": "Server Error"', $response);
        $this->assertNotContains('<!DOCTYPE html>', $response);
        $this->assertNotContains('"file"', $response);
        $this->assertNotContains('"line"', $response);
        $this->assertNotContains('"trace"', $response);
    }

}
