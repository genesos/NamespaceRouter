# NamespaceRouter
Silex Routing Extension Driven By Namespace

## Example

```
# index.php
$app = new Silex\Application;
$app->register(new NamespaceRouteServiceProvider(RootController::class, '/'));
$app->run();
```

```
# \AnyNamespace\RootController
# request '/' => 'root'
class RootController implements ControllerProviderInterface
{
	public function connect(ControllerCollection $controller_collection)
	{
		$controller_collection = $app['controllers_factory'];
		$controller_collection->get('/', function () {
			return new Response('root');
		});
		return $controller_collection;
	}
}
```

```
# \AnyNamespace\Blog
# request '/Blog/View' => 'blog view'
class Blog implements ControllerProviderInterface
{
	public function connect(ControllerCollection $controller_collection)
	{
		$controller_collection = $app['controllers_factory'];
		$controller_collection->get('/View', [$this, 'View']);
		return $controller_collection;
	}
	public function view()
	{
		return new Response('blog view');
	}
}
```

```
# \AnyNamespace\Site\Admin
# request '/Site/Admin/View' => 'admin view'
class Admin implements ControllerProviderInterface
{
	public function connect(ControllerCollection $controller_collection)
	{
		$controller_collection = $app['controllers_factory'];
		$controller_collection->get('/View', [$this, 'View']);
		return $controller_collection;
	}
	public function view()
	{
		return new Response('admin view');
	}
}
```
