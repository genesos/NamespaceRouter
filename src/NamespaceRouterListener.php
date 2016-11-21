<?php

namespace Gnf\NamespaceRouter;

use Psr\Log\LoggerInterface;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;

class NamespaceRouterListener extends RouterListener
{
	/**
	 * @var string
	 */
	private $root_controller;
	/**
	 * @var RequestContext
	 */
	private $request_context;
	/**
	 * @var null|LoggerInterface
	 */
	private $logger;
	/**
	 * @var string[]
	 */
	private $path_prefix;
	/**
	 * @var RouterListener
	 */
	private $default_route_listener;
	/**
	 * @var Application
	 */
	private $application;

	/**
	 * GnfRouterListener constructor.
	 *
	 * @param Application                                 $application
	 * @param RouterListener                              $default_route_listener
	 * @param RequestMatcherInterface|UrlMatcherInterface $matcher
	 * @param RequestStack                                $requestStack
	 * @param null|RequestContext                         $context
	 * @param null|LoggerInterface                        $logger
	 * @param ControllerProviderInterface                 $root_controller
	 * @param string                                      $path_prefix
	 */
	public function __construct(
		Application $application,
		$default_route_listener,
		$matcher,
		RequestStack $requestStack,
		$context,
		$logger,
		$root_controller,
		$path_prefix
	) {
		parent::__construct($matcher, $requestStack, $context, $logger);

		$this->application = $application;
		$this->default_route_listener = $default_route_listener;
		$this->request_context = $context ?: $matcher->getContext();
		$this->logger = $logger;
		$this->root_controller = $root_controller;
		$this->path_prefix = $path_prefix;
	}

	/**
	 * @param GetResponseEvent $event
	 */
	public function onKernelRequest(GetResponseEvent $event)
	{
		try {
			$this->default_route_listener->onKernelRequest($event);
			return;
		} catch (NotFoundHttpException $e) {
		}
		$request = $event->getRequest();
		$namespace_route = new NamespaceRouter(
			$this->application,
			$this->root_controller,
			$this->path_prefix,
			$request,
			$this->request_context
		);
		$parameters = $namespace_route->route();
		$this->processParameterOnSuccess($parameters, $request);
	}

	/**
	 * @param $parameters
	 * @param $request
	 */
	private function processParameterOnSuccess(array $parameters, Request $request)
	{
		if (null !== $this->logger) {
			$this->logger->info(
				'Matched route "{route}".',
				[
					'route' => isset($parameters['_route']) ? $parameters['_route'] : 'n/a',
					'route_parameters' => $parameters,
					'request_uri' => $request->getUri(),
					'method' => $request->getMethod(),
				]
			);
		}

		$request->attributes->add($parameters);
		unset($parameters['_route'], $parameters['_controller']);
		$request->attributes->set('_route_params', $parameters);
	}
}
