<?php

require_once __DIR__.'/../vendor/autoload.php';

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Persistence\ObjectManager;

$app = new Silex\Application();

$app['debug'] = true;

$app->get('/hello', function() {
    return 'Hello!';
});

$blogPosts = array(
	1 => array(
		'id' => '2',
		'date' => '2011-03-29',
		'author' => 'igorw',
		'title' => 'Using Silex',
		'body' => '...',
	),
);

$app->get('/blog', function () use ($blogPosts) {
	$output = '';
	foreach ($blogPosts as $post) {
		$output .= $post['title'];
		$output .= '<br />';
	}
	return $output;
});

$app->get('/blog/{id}', function (Silex\Application $app, $id) use ($blogPosts) {
	if (!isset($blogPosts[$id])) {
		$app->abort(404, "Publicar $id não existe");
	}

	$post = $blogPosts['id'];

	return "<h1>{$blogPosts[1]['title']}</h1>" . 
		   "<p>{$blogPosts[1]['body']}</p>";
});

/*$app->get('/user/{id}', function ($id) {
	return $id;
})->convert('id', function ($id) {
	return (int) $id;
});*/

class User {
	protected $user;

	public function __construct($user) {
		$this->user = (int) $user;
	}

	public function getUser()
	{
		return $this->user;
	}
}

$userProvider = function ($user) {
	return new User($user);
};

$app->get('/user/{user}', function ($user) {
	return $user->getUser();
})->convert('user', $userProvider);

$app->get('/user/{user}/edit', function ($user) {
	return $user->getUser();
})->convert('user', $userProvider);

class Post
{
	protected $slug;

	public function __construct($slug)
	{
		$this->slug = $slug;
	}

	public function getSlug()
	{
		return "Ola teste " . $this->slug;
	}
}

$callback = function ($post, Request $request) {
	return new Post($request->attributes->get('slug'));
};

$app->get('/blog/{id}/{slug}', function (Post $post) {
	return $post->getSlug();
})->convert('post', $callback);

class UserConverter
{
	private $orm;

	public function __construct($orm)
	{
		$this->orm = $orm;
	}

	public function convert($id)
	{
		if (null === $user) {
			throw new NotFoundHttpException(sprintf("User %d does not exist.", $id));
		}
		return $user;
	}
}

$app['converter.user'] = $app->share(function () {
	return new UserConverter('teste');
});

$app->get('/teste/{user}', function (User $user) {
	return 'teste';
})->convert('user', 'converter.user:convert');

$app->get('/biblioteca/{id}', function ($id) {
	return $id;
})->assert('id', '\d+');

$app->get('/minha-rota/{id}/{testeId}', function ($id, $testeId) {
	return 'id : ' . $id . ' testeId: ' . $testeId;
})->assert('id', '\d+')
  ->assert('testeId', '\d+');

/*$app->get('/{pageName}', function ($pageName) {
	return $pageName;
})->bind('homepage')
  ->value('pageName', 'index');*/

$app->get('/meu-site/{teste}', 'Foo::bar');

//namespace Acme {
	class Foo
	{
		public function bar($teste, Request $request, Application $app)
		{
			return $teste;
		}
	}
//}

$app['controllers']
	->assert('teste', '\d+');

$app->error(function (\Exception $e, $code) {
	switch ($code) {
		case 404: 
			$message = 'A página solicitada não pôde ser encontrado.';
			break;
		default: 
			$message = 'Lamentamos, mas algo deu terrivelmente errado.';
	}
	return new Response($message);
});

$app->run();
