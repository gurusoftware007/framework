<?php

namespace Illuminate\Tests\Queue;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Queue\RedisQueue;
use Illuminate\Container\Container;
use Illuminate\Queue\Jobs\RedisJob;
use Illuminate\Tests\Redis\InteractsWithRedis;

class RedisQueueIntegrationTest extends TestCase
{
    use InteractsWithRedis;

    /**
     * @var RedisQueue
     */
    private $queue;

    public function setUp()
    {
        parent::setUp();
        $this->setUpRedis();

        $this->queue = new RedisQueue($this->redis);
        $this->queue->setContainer(m::mock(Container::class));
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->tearDownRedis();
        m::close();
    }

    public function testExpiredJobsArePopped()
    {
        $jobs = [
            new RedisQueueIntegrationTestJob(0),
            new RedisQueueIntegrationTestJob(1),
            new RedisQueueIntegrationTestJob(2),
            new RedisQueueIntegrationTestJob(3),
        ];

        $this->queue->later(1000, $jobs[0]);
        $this->queue->later(-200, $jobs[1]);
        $this->queue->later(-300, $jobs[2]);
        $this->queue->later(-100, $jobs[3]);

        $this->assertEquals($jobs[2], unserialize(json_decode($this->queue->pop()->getRawBody())->data->command));
        $this->assertEquals($jobs[1], unserialize(json_decode($this->queue->pop()->getRawBody())->data->command));
        $this->assertEquals($jobs[3], unserialize(json_decode($this->queue->pop()->getRawBody())->data->command));
        $this->assertNull($this->queue->pop());

        $this->assertEquals(1, $this->redis->connection()->zcard('queues:default:delayed'));
        $this->assertEquals(3, $this->redis->connection()->zcard('queues:default:reserved'));
    }

    public function testPopProperlyPopsJobOffOfRedis()
    {
        // Push an item into queue
        $job = new RedisQueueIntegrationTestJob(10);
        $this->queue->push($job);

        // Pop and check it is popped correctly
        $before = time();
        /** @var RedisJob $redisJob */
        $redisJob = $this->queue->pop();
        $after = time();

        $this->assertEquals($job, unserialize(json_decode($redisJob->getRawBody())->data->command));
        $this->assertEquals(1, $redisJob->attempts());
        $this->assertEquals($job, unserialize(json_decode($redisJob->getReservedJob())->data->command));
        $this->assertEquals(1, json_decode($redisJob->getReservedJob())->attempts);
        $this->assertEquals($redisJob->getJobId(), json_decode($redisJob->getReservedJob())->id);

        // Check reserved queue
        $this->assertEquals(1, $this->redis->connection()->zcard('queues:default:reserved'));
        $result = $this->redis->connection()->zrangebyscore('queues:default:reserved', -INF, INF, ['WITHSCORES' => true]);
        $reservedJob = array_keys($result)[0];
        $score = $result[$reservedJob];
        $this->assertLessThanOrEqual($score, $before + 60);
        $this->assertGreaterThanOrEqual($score, $after + 60);
        $this->assertEquals($job, unserialize(json_decode($reservedJob)->data->command));
    }

    public function testPopProperlyPopsDelayedJobOffOfRedis()
    {
        // Push an item into queue
        $job = new RedisQueueIntegrationTestJob(10);
        $this->queue->later(-10, $job);

        // Pop and check it is popped correctly
        $before = time();
        $this->assertEquals($job, unserialize(json_decode($this->queue->pop()->getRawBody())->data->command));
        $after = time();

        // Check reserved queue
        $this->assertEquals(1, $this->redis->connection()->zcard('queues:default:reserved'));
        $result = $this->redis->connection()->zrangebyscore('queues:default:reserved', -INF, INF, ['WITHSCORES' => true]);
        $reservedJob = array_keys($result)[0];
        $score = $result[$reservedJob];
        $this->assertLessThanOrEqual($score, $before + 60);
        $this->assertGreaterThanOrEqual($score, $after + 60);
        $this->assertEquals($job, unserialize(json_decode($reservedJob)->data->command));
    }

    public function testPopPopsDelayedJobOffOfRedisWhenExpireNull()
    {
        $this->queue = new RedisQueue($this->redis, 'default', null, null);
        $this->queue->setContainer(m::mock(Container::class));

        // Push an item into queue
        $job = new RedisQueueIntegrationTestJob(10);
        $this->queue->later(-10, $job);

        // Pop and check it is popped correctly
        $before = time();
        $this->assertEquals($job, unserialize(json_decode($this->queue->pop()->getRawBody())->data->command));
        $after = time();

        // Check reserved queue
        $this->assertEquals(1, $this->redis->connection()->zcard('queues:default:reserved'));
        $result = $this->redis->connection()->zrangebyscore('queues:default:reserved', -INF, INF, ['WITHSCORES' => true]);
        $reservedJob = array_keys($result)[0];
        $score = $result[$reservedJob];
        $this->assertLessThanOrEqual($score, $before);
        $this->assertGreaterThanOrEqual($score, $after);
        $this->assertEquals($job, unserialize(json_decode($reservedJob)->data->command));
    }

    public function testNotExpireJobsWhenExpireNull()
    {
        $this->queue = new RedisQueue($this->redis, 'default', null, null);
        $this->queue->setContainer(m::mock(Container::class));

        // Make an expired reserved job
        $failed = new RedisQueueIntegrationTestJob(-20);
        $this->queue->push($failed);
        $beforeFailPop = time();
        $this->queue->pop();
        $afterFailPop = time();

        // Push an item into queue
        $job = new RedisQueueIntegrationTestJob(10);
        $this->queue->push($job);

        // Pop and check it is popped correctly
        $before = time();
        $this->assertEquals($job, unserialize(json_decode($this->queue->pop()->getRawBody())->data->command));
        $after = time();

        // Check reserved queue
        $this->assertEquals(2, $this->redis->connection()->zcard('queues:default:reserved'));
        $result = $this->redis->connection()->zrangebyscore('queues:default:reserved', -INF, INF, ['WITHSCORES' => true]);

        foreach ($result as $payload => $score) {
            $command = unserialize(json_decode($payload)->data->command);
            $this->assertInstanceOf(RedisQueueIntegrationTestJob::class, $command);
            $this->assertContains($command->i, [10, -20]);
            if ($command->i == 10) {
                $this->assertLessThanOrEqual($score, $before);
                $this->assertGreaterThanOrEqual($score, $after);
            } else {
                $this->assertLessThanOrEqual($score, $beforeFailPop);
                $this->assertGreaterThanOrEqual($score, $afterFailPop);
            }
        }
    }

    public function testExpireJobsWhenExpireSet()
    {
        $this->queue = new RedisQueue($this->redis, 'default', null, 30);
        $this->queue->setContainer(m::mock(Container::class));

        // Push an item into queue
        $job = new RedisQueueIntegrationTestJob(10);
        $this->queue->push($job);

        // Pop and check it is popped correctly
        $before = time();
        $this->assertEquals($job, unserialize(json_decode($this->queue->pop()->getRawBody())->data->command));
        $after = time();

        // Check reserved queue
        $this->assertEquals(1, $this->redis->connection()->zcard('queues:default:reserved'));
        $result = $this->redis->connection()->zrangebyscore('queues:default:reserved', -INF, INF, ['WITHSCORES' => true]);
        $reservedJob = array_keys($result)[0];
        $score = $result[$reservedJob];
        $this->assertLessThanOrEqual($score, $before + 30);
        $this->assertGreaterThanOrEqual($score, $after + 30);
        $this->assertEquals($job, unserialize(json_decode($reservedJob)->data->command));
    }

    public function testRelease()
    {
        //push a job into queue
        $job = new RedisQueueIntegrationTestJob(30);
        $this->queue->push($job);

        //pop and release the job
        /** @var \Illuminate\Queue\Jobs\RedisJob $redisJob */
        $redisJob = $this->queue->pop();
        $before = time();
        $redisJob->release(1000);
        $after = time();

        //check the content of delayed queue
        $this->assertEquals(1, $this->redis->connection()->zcard('queues:default:delayed'));

        $results = $this->redis->connection()->zrangebyscore('queues:default:delayed', -INF, INF, 'withscores');

        $payload = array_keys($results)[0];

        $score = $results[$payload];

        $this->assertGreaterThanOrEqual($before + 1000, $score);
        $this->assertLessThanOrEqual($after + 1000, $score);

        $decoded = json_decode($payload);

        $this->assertEquals(1, $decoded->attempts);
        $this->assertEquals($job, unserialize($decoded->data->command));

        //check if the queue has no ready item yet
        $this->assertNull($this->queue->pop());
    }

    public function testReleaseInThePast()
    {
        $job = new RedisQueueIntegrationTestJob(30);
        $this->queue->push($job);

        /** @var RedisJob $redisJob */
        $redisJob = $this->queue->pop();
        $redisJob->release(-3);

        $this->assertInstanceOf(RedisJob::class, $this->queue->pop());
    }

    public function testDelete()
    {
        $job = new RedisQueueIntegrationTestJob(30);
        $this->queue->push($job);

        /** @var \Illuminate\Queue\Jobs\RedisJob $redisJob */
        $redisJob = $this->queue->pop();

        $redisJob->delete();

        $this->assertEquals(0, $this->redis->connection()->zcard('queues:default:delayed'));
        $this->assertEquals(0, $this->redis->connection()->zcard('queues:default:reserved'));
        $this->assertEquals(0, $this->redis->connection()->llen('queues:default'));

        $this->assertNull($this->queue->pop());
    }

    public function testSize()
    {
        $this->assertEquals(0, $this->queue->size());
        $this->queue->push(new RedisQueueIntegrationTestJob(1));
        $this->assertEquals(1, $this->queue->size());
        $this->queue->later(60, new RedisQueueIntegrationTestJob(2));
        $this->assertEquals(2, $this->queue->size());
        $this->queue->push(new RedisQueueIntegrationTestJob(3));
        $this->assertEquals(3, $this->queue->size());
        $job = $this->queue->pop();
        $this->assertEquals(3, $this->queue->size());
        $job->delete();
        $this->assertEquals(2, $this->queue->size());
    }
}

class RedisQueueIntegrationTestJob
{
    public $i;

    public function __construct($i)
    {
        $this->i = $i;
    }

    public function handle()
    {
    }
}
