<?php namespace Illuminate\Http;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class FrameGuard implements HttpKernelInterface {

	/**
	 * The wrapped kernel implementation.
	 *
	 * @var \Symfony\Component\HttpKernel\HttpKernelInterface
	 */
	protected $app;

	/**
	 * Create a new CookieQueue instance.
	 *
	 * @param  \Symfony\Component\HttpKernel\HttpKernelInterface  $app
	 * @return void
	 */
	public function __construct(HttpKernelInterface $app)
	{
		$this->app = $app;
	}

	/**
	 * Handle the given request and get the response.
	 *
	 * @implements HttpKernelInterface::handle
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @param  int   $type
	 * @param  bool  $catch
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
	{
		$response = $this->app->handle($request, $type, $catch);

		$response->headers->set('X-Frame-Options', 'SAMEORIGIN');

		return $response;
	}

}