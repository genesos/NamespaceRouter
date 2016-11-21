<?php

namespace Gnf\NamespaceRouter;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;

class NamespaceRouter
{
	const MAX_TRAVEL_COUNT = 1000;
	private $root_controller;
	/**
	 * @var Request
	 */
	private $request;
	/**
	 * @var RequestContext
	 */
	private $request_context;
	private $path_prefix;
	/**
	 * @var Application
	 */
	private $application;

	/**
	 * NameSpaceRouter constructor.
	 *
	 * @param Application                               $application
	 * @param                                           $root_controller
	 * @param                                           $path_prefix
	 * @param Request                                   $request
	 * @param RequestContext                            $request_context
	 */
	public function __construct(Application $application, $root_controller, $path_prefix, $request, RequestContext $request_context)
	{
		$this->application = $application;
		$this->root_controller = $root_controller;
		$this->path_prefix = $path_prefix;
		$this->request = $request;
		$this->request_context = $request_context;
	}

	public function route()
	{
		$pathinfo = $this->request->getPathInfo();
		$pathinfo_tokens = array_filter(explode('/', $pathinfo));
		$pathinfo_tokens = $this->bypassPrefixIfValid($pathinfo_tokens);
		$traveler = new NamespaceTraveler(
			$this->application,
			$this->root_controller,
			$this->path_prefix,
			$pathinfo_tokens,
			$this->request,
			$this->request_context
		);
		$count = 0;
		while (!$traveler->travelAndFound()) {
			$count++;
			if ($count > self::MAX_TRAVEL_COUNT) {
				$this->throwResourceNotFoundException($pathinfo);
			}
		}
		$bag = $traveler->openBag();
		if ($bag === null) {
			$this->throwResourceNotFoundException($pathinfo);
		}
		return $bag;
	}

	/**
	 * @param $pathinfo_tokens
	 *
	 * @return array
	 * @throws ResourceNotFoundException
	 */
	private function bypassPrefixIfValid($pathinfo_tokens)
	{
		$prefix_length = count($this->path_prefix);
		$pathinfo_prefix = array_slice($pathinfo_tokens, 0, $prefix_length);
		if ($this->path_prefix === $pathinfo_prefix) {
			return array_slice($pathinfo_tokens, $prefix_length);
		}

		$this->throwResourceNotFoundException('/' . implode('/', $pathinfo_tokens));
		return [];
	}

	/**
	 * @param $pathinfo
	 *
	 * @throws ResourceNotFoundException
	 */
	private function throwResourceNotFoundException($pathinfo):void
	{
		throw new ResourceNotFoundException(sprintf('No routes found for "%s".', $pathinfo));
	}
}
