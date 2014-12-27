<?php namespace Illuminate\Bus;

use Closure;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Bus\HandlerResolver;
use Illuminate\Contracts\Queue\ShouldBeQueued;
use Illuminate\Contracts\Bus\QueueingDispatcher;
use Illuminate\Contracts\Bus\Dispatcher as DispatcherContract;

class Dispatcher implements DispatcherContract, QueueingDispatcher, HandlerResolver {

	/**
	 * The container implementation.
	 *
	 * @var \Illuminate\Contracts\Container\Container
	 */
	protected $container;

	/**
	 * The queue resolver callback.
	 *
	 * @var \Closure|null
	 */
	protected $queueResolver;

	/**
	 * All of the command to handler mappings.
	 *
	 * @var array
	 */
	protected $mappings = [];

	/**
	 * The fallback mapping Closure.
	 *
	 * @var \Closure
	 */
	protected $mapper;

	/**
	 * Create a new command dispatcher instance.
	 *
	 * @param  \Illuminate\Contracts\Container\Container  $container
	 * @param  \Closure|null $queueResolver
	 * @return void
	 */
	public function __construct(Container $container, Closure $queueResolver = null)
	{
		$this->container = $container;
		$this->queueResolver = $queueResolver;
	}

	/**
	 * Dispatch a command to its appropriate handler.
	 *
	 * @param  mixed  $command
	 * @param  \Closure  $afterResolving
	 * @return mixed
	 */
	public function dispatch($command, Closure $afterResolving = null)
	{
		if ($this->queueResolver && $command instanceof ShouldBeQueued)
		{
			return $this->dispatchToQueue($command);
		}
		else
		{
			return $this->dispatchNow($command);
		}
	}

	/**
	 * Dispatch a command to its appropriate handler in the current process.
	 *
	 * @param  mixed  $command
	 * @param  \Closure  $afterResolving
	 * @return mixed
	 */
	public function dispatchNow($command, Closure $afterResolving = null)
	{
		if ($command instanceof SelfHandling)
			return $this->container->call([$command, 'handle']);

		$handler = $this->resolveHandler($command);

		if ($afterResolving)
			call_user_func($afterResolving, $handler);

		return call_user_func(
			[$handler, $this->getHandlerMethod($command)], $command
		);
	}

	/**
	 * Dispatch a command to its appropriate handler behind a queue.
	 *
	 * @param  mixed  $command
	 * @return mixed
	 *
	 * @throws \RuntimeException
	 */
	public function dispatchToQueue($command)
	{
		$queue = call_user_func($this->queueResolver);

		if ( ! $queue instanceof Queue)
		{
			throw new \RuntimeException("Queue resolver did not return a Queue implementation.");
		}

		$queue->push($command);
	}

	/**
	 * Get the handler instance for the given command.
	 *
	 * @param  mixed  $command
	 * @return mixed
	 */
	public function resolveHandler($command)
	{
		return $this->container->make($this->getHandlerClass($command));
	}

	/**
	 * Get the handler class for the given command.
	 *
	 * @param  mixed  $command
	 * @return string
	 */
	public function getHandlerClass($command)
	{
		return $this->inflectSegment($command, 0);
	}

	/**
	 * Get the handler method for the given command.
	 *
	 * @param  mixed  $command
	 * @return string
	 */
	public function getHandlerMethod($command)
	{
		return $this->inflectSegment($command, 1);
	}

	/**
	 * Get the given handler segment for the given command.
	 *
	 * @param  mixed  $command
	 * @param  int  $segment
	 * @return string
	 */
	protected function inflectSegment($command, $segment)
	{
		$className = get_class($command);

		if (isset($this->mappings[$className]))
		{
			return $this->getMappingSegment($className, $segment);
		}
		elseif ($this->mapper)
		{
			return $this->getMapperSegment($command, $segment);
		}

		throw new \InvalidArgumentException("No handler registered for command [{$className}]");
	}

	/**
	 * Get the given segment from a given class handler.
	 *
	 * @param  string  $className
	 * @param  int  $segment
	 * @return string
	 */
	protected function getMappingSegment($className, $segment)
	{
		return explode('@', $this->mappings[$className])[$segment];
	}

	/**
	 * Get the given segment from a given class handler using the custom mapper.
	 *
	 * @param  mixed  $command
	 * @param  int  $segment
	 * @return string
	 */
	protected function getMapperSegment($command, $segment)
	{
		return explode('@', call_user_func($this->mapper, $command))[$segment];
	}

	/**
	 * Register command to handler mappings.
	 *
	 * @param  array  $commands
	 * @return void
	 */
	public function maps(array $commands)
	{
		$this->mappings = array_merge($this->mappings, $commands);
	}

	/**
	 * Register a fallback mapper callback.
	 *
	 * @param  \Closure  $mapper
	 * @return void
	 */
	public function mapUsing(Closure $mapper)
	{
		$this->mapper = $mapper;
	}

}
