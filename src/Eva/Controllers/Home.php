<?php
namespace Eva\Controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Response;

class Home implements ControllerProviderInterface {

	public function connect(Application $app) {
		$content = array();
		$controllers = $app['controllers_factory'];
		$controllers->match('/', array($this,'home'));
		return $controllers;
	}

	public function home(Application $app) {
		return $app->redirect('/pz/page/');
// 		return $app['twig']->render("home.twig", array(
// 			'_menu' => 'home',
// 		));
	}
}