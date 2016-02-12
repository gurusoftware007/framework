<?php

use Mockery as m;
use Illuminate\Cache\ArrayStore;

class CacheTaggedCacheTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testCacheCanBeSavedWithMultipleTags()
    {
        $store = new ArrayStore;
        $tags = ['bop', 'zap'];
        $store->tags($tags)->put('foo', 'bar', 10);
        $this->assertEquals('bar', $store->tags($tags)->get('foo'));
    }

    public function testCacheCanBeSetWithDatetimeArgument()
    {
        $store = new ArrayStore;
        $tags = ['bop', 'zap'];
        $duration = new DateTime();
        $duration->add(new DateInterval('PT10M'));
        $store->tags($tags)->put('foo', 'bar', $duration);
        $this->assertEquals('bar', $store->tags($tags)->get('foo'));
    }

    public function testCacheSavedWithMultipleTagsCanBeFlushed()
    {
        $store = new ArrayStore;
        $tags1 = ['bop', 'zap'];
        $store->tags($tags1)->put('foo', 'bar', 10);
        $tags2 = ['bam', 'pow'];
        $store->tags($tags2)->put('foo', 'bar', 10);
        $store->tags('zap')->flush();
        $this->assertNull($store->tags($tags1)->get('foo'));
        $this->assertEquals('bar', $store->tags($tags2)->get('foo'));
    }

    public function testTagsWithStringArgument()
    {
        $store = new ArrayStore;
        $store->tags('bop')->put('foo', 'bar', 10);
        $this->assertEquals('bar', $store->tags('bop')->get('foo'));
    }

    public function testTagsCacheForever()
    {
        $store = new ArrayStore;
        $tags = ['bop', 'zap'];
        $store->tags($tags)->forever('foo', 'bar');
        $this->assertEquals('bar', $store->tags($tags)->get('foo'));
    }

    public function testRedisCacheTagsPushForeverKeysCorrectly()
    {
        $store = m::mock('Illuminate\Contracts\Cache\Store');
        $tagSet = m::mock('Illuminate\Cache\TagSet', [$store, ['foo', 'bar']]);
        $tagSet->shouldReceive('getNamespace')->andReturn('foo|bar');
        $tagSet->shouldReceive('getNames')->andReturn(['foo', 'bar']);
        $redis = new Illuminate\Cache\RedisTaggedCache($store, $tagSet);
        $store->shouldReceive('getPrefix')->andReturn('prefix:');
        $store->shouldReceive('connection')->andReturn($conn = m::mock('StdClass'));
        $conn->shouldReceive('lpush')->once()->with('prefix:foo:forever', 'prefix:'.sha1('foo|bar').':key1');
        $conn->shouldReceive('lpush')->once()->with('prefix:bar:forever', 'prefix:'.sha1('foo|bar').':key1');
        $store->shouldReceive('forever')->with(sha1('foo|bar').':key1', 'key1:value');

        $redis->forever('key1', 'key1:value');
    }

    public function testRedisCacheTagsPushStandardKeysCorrectly()
    {
        $store = m::mock('Illuminate\Contracts\Cache\Store');
        $tagSet = m::mock('Illuminate\Cache\TagSet', [$store, ['foo', 'bar']]);
        $tagSet->shouldReceive('getNamespace')->andReturn('foo|bar');
        $tagSet->shouldReceive('getNames')->andReturn(['foo', 'bar']);
        $redis = new Illuminate\Cache\RedisTaggedCache($store, $tagSet);
        $store->shouldReceive('getPrefix')->andReturn('prefix:');
        $store->shouldReceive('connection')->andReturn($conn = m::mock('StdClass'));
        $conn->shouldReceive('lpush')->once()->with('prefix:foo:standard', 'prefix:'.sha1('foo|bar').':key1');
        $conn->shouldReceive('lpush')->once()->with('prefix:bar:standard', 'prefix:'.sha1('foo|bar').':key1');
        $store->shouldReceive('push')->with(sha1('foo|bar').':key1', 'key1:value');

        $redis->put('key1', 'key1:value');
    }

    public function testRedisCacheTagsCanBeFlushed()
    {
        $store = m::mock('Illuminate\Contracts\Cache\Store');
        $tagSet = m::mock('Illuminate\Cache\TagSet', [$store, ['foo', 'bar']]);
        $tagSet->shouldReceive('getNamespace')->andReturn('foo|bar');
        $redis = new Illuminate\Cache\RedisTaggedCache($store, $tagSet);
        $store->shouldReceive('getPrefix')->andReturn('prefix:');
        $store->shouldReceive('connection')->andReturn($conn = m::mock('StdClass'));

        // Forever tag keys
        $conn->shouldReceive('lrange')->once()->with('prefix:foo:forever', 0, -1)->andReturn(['key1', 'key2']);
        $conn->shouldReceive('lrange')->once()->with('prefix:bar:forever', 0, -1)->andReturn(['key3']);
        $conn->shouldReceive('del')->once()->with('key1', 'key2');
        $conn->shouldReceive('del')->once()->with('key3');
        $conn->shouldReceive('del')->once()->with('prefix:foo:forever');
        $conn->shouldReceive('del')->once()->with('prefix:bar:forever');

        // Standard tag keys
        $conn->shouldReceive('lrange')->once()->with('prefix:foo:standard', 0, -1)->andReturn(['key4', 'key5']);
        $conn->shouldReceive('lrange')->once()->with('prefix:bar:standard', 0, -1)->andReturn(['key6']);
        $conn->shouldReceive('del')->once()->with('key4', 'key5');
        $conn->shouldReceive('del')->once()->with('key6');
        $conn->shouldReceive('del')->once()->with('prefix:foo:standard');
        $conn->shouldReceive('del')->once()->with('prefix:bar:standard');

        $tagSet->shouldReceive('reset')->once();

        $redis->flush();
    }
}
