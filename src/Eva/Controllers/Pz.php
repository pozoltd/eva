<?php
namespace Eva\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class Pz extends Route
{
    public function route(Application $app, Request $request, $url = null)
    {
        if (empty($url) && $url !== null) {
            return $this->app->abort(301, '/pz/secured/pages');
        }
        return parent::route($app, $request, $url);
    }

    protected function getNodes()
    {
        return $this->app['cms']->nodes();
    }
}