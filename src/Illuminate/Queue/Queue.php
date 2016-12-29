<?php

namespace Illuminate\Queue;

use Illuminate\Support\Arr;
use Illuminate\Container\Container;

abstract class Queue
{
    use CalculatesDelays;

    /**
     * The IoC container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * The encrypter implementation.
     *
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * Push a new job onto the queue.
     *
     * @param  string  $queue
     * @param  string  $job
     * @param  mixed   $data
     * @return mixed
     */
    public function pushOn($queue, $job, $data = '')
    {
        return $this->push($job, $data, $queue);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  string  $queue
     * @param  \DateTime|int  $delay
     * @param  string  $job
     * @param  mixed   $data
     * @return mixed
     */
    public function laterOn($queue, $delay, $job, $data = '')
    {
        return $this->later($delay, $job, $data, $queue);
    }

    /**
     * Push an array of jobs onto the queue.
     *
     * @param  array   $jobs
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function bulk($jobs, $data = '', $queue = null)
    {
        foreach ((array) $jobs as $job) {
            $this->push($job, $data, $queue);
        }
    }

    /**
     * Create a payload string from the given job and data.
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return string
     *
     * @throws \Illuminate\Queue\InvalidPayloadException
     */
    protected function createPayload($job, $data = '', $queue = null)
    {
        $payload = json_encode($this->createPayloadArray($job, $data, $queue));

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidPayloadException;
        }

        return $payload;
    }

    /**
     * Create a payload array from the given job and data.
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return array
     */
    protected function createPayloadArray($job, $data = '', $queue = null)
    {
        return is_object($job)
                    ? $this->createObjectPayload($job)
                    : $this->createPlainPayload($job, $data);
    }

    /**
     * Create a payload for an object-based queue handler.
     *
     * @param  mixed  $job
     * @return array
     */
    protected function createObjectPayload($job)
    {
        return [
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'maxTries' => isset($job->tries) ? $job->tries : null,
            'timeout' => isset($job->timeout) ? $job->timeout : null,
            'data' => [
                'commandName' => get_class($job),
                'command' => serialize(clone $job),
            ],
        ];
    }

    /**
     * Create a typical, "plain" queue payload array.
     *
     * @param  string  $job
     * @param  mixed  $data
     * @return array
     */
    protected function createPlainPayload($job, $data)
    {
        return ['job' => $job, 'data' => $data];
    }

    /**
     * Set additional meta on a payload string.
     *
     * @param  string  $payload
     * @param  string  $key
     * @param  string  $value
     * @return string
     *
     * @throws \Illuminate\Queue\InvalidPayloadException
     */
    // protected function setMeta($payload, $key, $value)
    // {
    //     $payload = json_decode($payload, true);

    //     $payload = json_encode(Arr::set($payload, $key, $value));

    //     if (JSON_ERROR_NONE !== json_last_error()) {
    //         throw new InvalidPayloadException;
    //     }

    //     return $payload;
    // }

    /**
     * Set the IoC container instance.
     *
     * @param  \Illuminate\Container\Container  $container
     * @return void
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }
}
