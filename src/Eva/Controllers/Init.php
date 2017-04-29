<?php
namespace Eva\Controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Init implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $controllers->match('/models/', array($this, 'models'));
        return $controllers;
    }

    public function models(Application $app, Request $request)
    {

        return new Response('OK');
    }
}