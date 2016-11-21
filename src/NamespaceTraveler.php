<?php

namespace Gnf\NamespaceRouter;

use ReflectionClass;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\Provider\Routing\RedirectableUrlMatcher;
use Silex\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;

class NamespaceTraveler
{
	/**
	 * @var array
	 */
	private $passed_tokens;
	/**
	 * @var array
	 */
	private $remain_tokens;
	/**
	 * @var Request
	 */
	private $request;
	/**
	 * @var RequestContext
	 */
	private $request_context;
	/**
	 * @var array
	 */
	private $parameters;
	private $path_prefix;
	/**
	 * @var Application
	 */
	private $application;

	/**
	 * NamespaceRouterTraveler constructor.
	 *
	 * @param Application        $application
	 * @param                    $root_controller
	 * @param                    $path_prefix
	 * @param array              $pathinfo_tokens
	 * @param Request            $request
	 * @param RequestContext     $request_context
	 */
	public function __construct(Application $application, $root_controller, $path_prefix, $pathinfo_tokens, $request, $request_context)
	{
		$this->application = $application;
		$this->routing_station = $this->getRoutingStationInstance($root_controller);
		$this->path_prefix = $path_prefix;
		$this->passed_tokens = [];
		$this->remain_tokens = $pathinfo_tokens;
		$this->request = $request;
		$this->request_context = $request_context;
		$this->parameters = null;
	}

	/**
	 * @param ControllerProviderInterface $station_class_as_string
	 *
	 * @return ControllerProviderInterface
	 */
	private function getRoutingStationInstance($station_class_as_string)
	{
		return new $station_class_as_string;
	}

	public function travelAndFound()
	{
		$found_on_this_station = $this->checkStation($this->routing_station, $this->passed_tokens, $this->request);
		if ($found_on_this_station) {
			return true;
		}
		$next_routing_station = $this->findNextStation($this->routing_station);
		if ($next_routing_station) {
			$this->routing_station = $next_routing_station;
			return false;
		}
		return true;
	}

	private function checkStation(ControllerProviderInterface $routing_station, $passed_tokens, Request $request)
	{
		try {
			$route_list = $this->listPlacesInStation($routing_station, $passed_tokens);
			$redirectable_url_matcher = new RedirectableUrlMatcher($route_list, $this->request_context);
			$parameters = $redirectable_url_matcher->matchRequest($request);
			$this->pickupBag($parameters);
		} catch (NotFoundHttpException $e) {
			return false;
		} catch (ResourceNotFoundException $e) {
			return false;
		}
		return true;
	}

	private function listPlacesInStation(ControllerProviderInterface $routing_station, $passed_tokens)
	{
		$controller_collection = $this->getStationPublicMethodToController($routing_station);
		$base_controller_collection = new ControllerCollection(new Route());
		$base_controller_collection->mount(
			implode('/', array_merge($this->path_prefix, $passed_tokens)),
			$controller_collection
		);
		$route_collection = $base_controller_collection->flush();
		return $route_collection;
	}

	private function getStationPublicMethodToController(ControllerProviderInterface $routing_station)
	{
		$controller_collection = $routing_station->connect($this->application);
		if (!$controller_collection instanceof ControllerCollection) {
			throw new \LogicException(
				sprintf(
					'The method "%s::connect" must return a "ControllerCollection" instance. Got: "%s"',
					get_class($routing_station),
					(is_object($controller_collection) ? get_class($controller_collection) : gettype($controller_collection))
				)
			);
		}
		return $controller_collection;
	}

	private function pickupBag($parameters)
	{
		$this->parameters = $parameters;
	}

	private function findNextStation(ControllerProviderInterface $routing_station)
	{
		$remain_tokens = $this->remain_tokens;
		$current_tokens = [];
		$class_dir = $this->getStationClassDir($routing_station);
		$namespace = $this->getStationNamespace($routing_station);
		$found = false;
		if (count($remain_tokens) == 0) {
			return null;
		}
		while (count($remain_tokens)) {
			$current_tokens[] = array_shift($remain_tokens);
			if (file_exists($class_dir . '/' . implode('/', $current_tokens) . '.php')) {
				$found = true;
				break;
			}
			if (is_dir($class_dir . '/' . implode('/', $current_tokens))) {
				continue;
			}
			break;
		}
		if (!$found) {
			return null;
		}
		$next_station_class = $namespace . '\\' . implode('\\', $current_tokens);
		$reflection_class = new ReflectionClass($next_station_class);
		if (!$reflection_class) {
			return null;
		}
		if (!$reflection_class->isSubclassOf(ControllerProviderInterface::class)) {
			return null;
		}

		$this->remain_tokens = $remain_tokens;
		array_splice($this->passed_tokens, -1, 0, $current_tokens);
		return new $next_station_class;
	}

	private function getStationClassDir($routing_station)
	{
		$reflection_class = new ReflectionClass($routing_station);
		$filename = $reflection_class->getFileName();
		return dirname($filename);
	}

	private function getStationNamespace($routing_station)
	{
		$reflection_class = new ReflectionClass($routing_station);
		$namespace = $reflection_class->getNamespaceName();
		return $namespace;
	}

	public function openBag()
	{
		return $this->parameters;
	}
}
