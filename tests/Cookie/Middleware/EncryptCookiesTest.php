<?php

use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Container\Container;
use Illuminate\Encryption\Encrypter;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Symfony\Component\HttpFoundation\Cookie;

class EncryptCookiesTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Illuminate\Routing\Router
     */
    protected $router;

    protected $setCookiePath = 'cookie/set';
    protected $queueCookiePath = 'cookie/queue';

    public function setUp()
    {
        parent::setUp();

        $container = new Container;
        $container->singleton('Illuminate\Contracts\Encryption\Encrypter', function () {
            return new Encrypter(str_repeat('a', 16));
        });

        $this->router = new Router(new Illuminate\Events\Dispatcher, $container);
    }
    public function testSetCookieEncryption()
    {
        $this->router->get($this->setCookiePath, [
            'middleware' => 'EncryptCookiesTestMiddleware',
            'uses'=>'EncryptCookiesTestController@setCookies'
        ]);

        $response = $this->router->dispatch(Request::create($this->setCookiePath, 'GET'));

        $cookies = $response->headers->getCookies();
        $this->assertEquals(2, count($cookies));
        $this->assertEquals('encrypted_cookie', $cookies[0]->getName());
        $this->assertNotEquals('value', $cookies[0]->getValue());
        $this->assertEquals('unencrypted_cookie', $cookies[1]->getName());
        $this->assertEquals('value', $cookies[1]->getValue());
    }
    public function testQueuedCookieEncryption()
    {
        $this->router->get($this->queueCookiePath, [
            'middleware' => ['EncryptCookiesTestMiddleware', 'AddQueuedCookiesToResponseTestMiddleware'],
            'uses'=>'EncryptCookiesTestController@queueCookies'
        ]);

        $response = $this->router->dispatch(Request::create($this->queueCookiePath, 'GET'));

        $cookies = $response->headers->getCookies();
        $this->assertEquals(2, count($cookies));
        $this->assertEquals('encrypted_cookie', $cookies[0]->getName());
        $this->assertNotEquals('value', $cookies[0]->getValue());
        $this->assertEquals('unencrypted_cookie', $cookies[1]->getName());
        $this->assertEquals('value', $cookies[1]->getValue());
    }
}

class EncryptCookiesTestController extends Illuminate\Routing\Controller
{
    public function setCookies()
    {
        $response = new Response();
        $response->headers->setCookie(new Cookie('encrypted_cookie', 'value'));
        $response->headers->setCookie(new Cookie('unencrypted_cookie', 'value'));

        return $response;
    }
    public function queueCookies()
    {
        return new Response();
    }
}

class EncryptCookiesTestMiddleware extends EncryptCookies
{
    protected $except = [
        'unencrypted_cookie',
    ];
}

class AddQueuedCookiesToResponseTestMiddleware extends AddQueuedCookiesToResponse
{
    public function __construct()
    {
        $cookie = new Illuminate\Cookie\CookieJar;
        $cookie->queue(new Cookie('encrypted_cookie', 'value'));
        $cookie->queue(new Cookie('unencrypted_cookie', 'value'));

        $this->cookies = $cookie;
    }
}
