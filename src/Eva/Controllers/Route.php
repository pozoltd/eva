<?php

namespace Eva\Controllers;

use Eva\Route\Tree;
use Eva\Route\URL;
use Eva\Route\Nav;
use Eva\Tools\Utils;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Request;


abstract class Route implements ControllerProviderInterface
{
    public function __construct(Application $app, $options = array())
    {
        $this->app = $app;
    }

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $controllers->match('{url}', array($this, 'route'))->assert('url', '.*');
        return $controllers;
    }

    public function route(Application $app, Request $request, $url)
    {
        $options = $this->getOptionsFromUrl($url);
        return $app['twig']->render($options['page']->twig, $options);
    }

    protected function getOptionsFromUrl($url = null)
    {
        if (!$url) {
            $url = str_replace(DOMAIN, '', URL::getURL());
        }
        $url = trim($url, '/');
        $args = explode('/', $url);

        $nav = new Nav($this->getNodes());
        $node = $nav->getNodeByField('url', '/' . $url . '/');
        if (!$node) {
            for ($i = count($args), $il = 0; $i > $il; $i--) {
                $parts = array_slice($args, 0, $i);
                $result = $nav->getNodeByField('url', '/' . implode('/', $parts) . '/');
                if ($result) {
                    $node = $result;
                    break;
                }
            }
        }
        if (!$node) {
            $this->app->abort(404);
        }
        return array(
            'page' => $node,
            'args' => $args,
            'returnUrl' => $this->app['request']->get('returnUrl') ?: '/' . $url . '/',
        );
    }

    abstract protected function getNodes();
}