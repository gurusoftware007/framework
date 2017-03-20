<?php

namespace Illuminate\Tests\Console\Scheduling;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\CacheOverlappingStrategy;

class CacheOverlappingStrategyTest extends TestCase
{
    /**
     * @var CacheOverlappingStrategy
     */
    protected $cacheOverlappingStrategy;

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cacheRepository;

    public function setUp()
    {
        parent::setUp();

        $this->cacheRepository = m::mock('Illuminate\Contracts\Cache\Repository');
        $this->cacheOverlappingStrategy = new CacheOverlappingStrategy($this->cacheRepository);
    }

    public function testPreventOverlap()
    {
        $cacheOverlappingStrategy = $this->cacheOverlappingStrategy;

        $this->cacheRepository->shouldReceive('add');

        $event = new Event($this->cacheOverlappingStrategy, 'command');

        $cacheOverlappingStrategy->prevent($event);
    }

    public function testPreventOverlapFails()
    {
        $cacheOverlappingStrategy = $this->cacheOverlappingStrategy;

        $this->cacheRepository->shouldReceive('add')->andReturn(false);

        $event = new Event($this->cacheOverlappingStrategy, 'command');

        $this->assertFalse($cacheOverlappingStrategy->prevent($event));
    }

    public function testOverlapsForNonRunningTask()
    {
        $cacheOverlappingStrategy = $this->cacheOverlappingStrategy;

        $this->cacheRepository->shouldReceive('has')->andReturn(false);

        $event = new Event($this->cacheOverlappingStrategy, 'command');

        $this->assertFalse($cacheOverlappingStrategy->overlaps($event));
    }

    public function testOverlapsForRunningTask()
    {
        $cacheOverlappingStrategy = $this->cacheOverlappingStrategy;

        $this->cacheRepository->shouldReceive('has')->andReturn(true);

        $event = new Event($this->cacheOverlappingStrategy, 'command');

        $this->assertTrue($cacheOverlappingStrategy->overlaps($event));
    }

    public function testResetOverlap()
    {
        $cacheOverlappingStrategy = $this->cacheOverlappingStrategy;

        $this->cacheRepository->shouldReceive('forget');

        $event = new Event($this->cacheOverlappingStrategy, 'command');

        $cacheOverlappingStrategy->reset($event);
    }
}
