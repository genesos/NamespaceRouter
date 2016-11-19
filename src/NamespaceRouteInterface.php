<?php

namespace Gnf\NamespaceRouter;

use Silex\ControllerCollection;

interface NamespaceRouteInterface
{
	public function connect(ControllerCollection $controller_collection);
}