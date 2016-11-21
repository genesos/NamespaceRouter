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
# AnyNamespace\RootController
# request '/' => 'root'
class RootController implements NamespaceRouteInterface
{
	public function connect(ControllerCollection $controller_collection)
	{
		$controller_collection->get('/', function () {
			return new Response('root');
		});
	}

	public function index(Request $request)
	{
		return new Response('get type ' . $request->get('type'));
	}
}
```

```
# AnyNamespace\Blog
# request '/Blog/view' => 'blog view'
class Blog implements NamespaceRouteInterface
{
	public function view()
	{
		return new Response('blog view');
	}
}
```

```
# AnyNamespace\Site\Blog
# request '/Site/Blog/view' => 'admin view'
class Admin implements NamespaceRouteInterface
{
	public function connect(ControllerCollection $controller_collection)
	{
	}
	public function view()
	{
		return new Response('admin view');
	}
}
```