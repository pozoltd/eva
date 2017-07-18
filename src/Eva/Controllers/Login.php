<?php

namespace Eva\Controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class Login implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $controllers->match('/pz', array($this, 'pz'));
        $controllers->match('/pz/login', array($this, 'login'));
        return $controllers;
    }

    public function pz(Application $app, Request $request) {
        return $app->redirect('/pz/secured');
    }

    public function login(Application $app, Request $request)
    {
        return $app['twig']->render("login.twig", array(
                'error' => $app['security.last_error']($request),
                'last_username' => $app['session']->get('_security.last_username'))
        );
    }
}