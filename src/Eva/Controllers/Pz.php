<?php
namespace Eva\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class Pz extends Route
{
    public function route(Application $app, Request $request, $url)
    {
        if (!$url) {
            return $this->app->abort(301, '/pz/pages/');
        }
        return parent::route($app, $request, $url);
    }

    protected function getNodes()
    {
        return $this->app['cms']->nodes();
    }
}