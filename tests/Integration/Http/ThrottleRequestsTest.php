<?php

namespace Illuminate\Tests\Integration\Http;

use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Middleware\ThrottleRequests;

/**
 * @group integration
 */
class ThrottleRequestsTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('cache.default', 'redis');
    }

    public function setup()
    {
        parent::setup();

        resolve('redis')->flushall();
    }

    public function test_lock_opens_immediately_after_decay()
    {
        Carbon::setTestNow(null);

        Route::get('/', function () {
            return 'yes';
        })->middleware(ThrottleRequests::class.':2,1');

        $response = $this->withoutExceptionHandling()->get('/');
        $this->assertEquals('yes', $response->getContent());
        $this->assertEquals(2, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals(1, $response->headers->get('X-RateLimit-Remaining'));

        $response = $this->withoutExceptionHandling()->get('/');
        $this->assertEquals('yes', $response->getContent());
        $this->assertEquals(2, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals(0, $response->headers->get('X-RateLimit-Remaining'));

        Carbon::setTestNow(
            Carbon::now()->addSeconds(58)
        );

        try {
            $response = $this->withoutExceptionHandling()->get('/');
        } catch (\Throwable $e) {
            $this->assertEquals(429, $e->getStatusCode());
            $this->assertEquals(2, $e->getHeaders()['X-RateLimit-Limit']);
            $this->assertEquals(0, $e->getHeaders()['X-RateLimit-Remaining']);
            $this->assertEquals(2, $e->getHeaders()['Retry-After']);
            $this->assertEquals(now()->timestamp + 2, $e->getHeaders()['X-RateLimit-Reset']);
        }
    }
}
