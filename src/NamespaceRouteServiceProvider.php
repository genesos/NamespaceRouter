<?php

namespace Gnf\NamespaceRouter;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

class NamespaceRouteServiceProvider implements ServiceProviderInterface
{
	/**
	 * @var
	 */
	private $root_controller;
	/**
	 * @var string
	 */
	private $path_prefix;

	/**
	 * NamespaceRouteServiceProvider constructor.
	 *
	 * @param NamespaceRouteInterface $root_controller
	 * @param string                  $path_prefix_str
	 */
	public function __construct($root_controller, $path_prefix_str = '/')
	{
		$this->root_controller = $root_controller;
		$this->path_prefix = array_values(array_filter(explode('/', $path_prefix_str)));
	}

	public function register(Container $app)
	{
		$app->extend(
			'routing.listener',
			function ($default_route_listener, $app) {
				return new NamespaceRouterListener(
					$default_route_listener,
					$app['request_matcher'],
					$app['request_stack'],
					$app['request_context'],
					$app['logger'],
					$this->root_controller,
					$this->path_prefix
				);
			}
		);
	}
}
