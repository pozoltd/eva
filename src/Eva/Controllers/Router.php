<?php
namespace Pz\Controllers;

use Imagick;
use Pz\Common\Utils;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class Router implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $controllers->match('{url}', array($this, 'route'))->assert('url', '.*');
        return $controllers;
    }

    public function route(Application $app, Request $request, $url)
    {
        $className = $app['pageClass'];
        $page = $className::findByField($app['em'], 'url', trim($url, '/'));
        if (!$page) {
            $page = $className::findByField($app['em'], 'url', trim($url, '/') . '/');
        }
        if ($page) {
            if ($page->type == 2) {
                return $app->redirect($page->redirectTo);
            }
            return $app['twig']->render($page->objTemplate()->filename, array(
                'pageBuilder' => $page,
                'params' => array(),
            ));
        }

        //If no exact match, start looking for closest
        $url = rtrim($url, '/');
        $args = explode('/', $url);
        for ($i = count($args), $il = 0; $i > $il; $i--) {
            //Gradually, take out last piece
            $parts = array_slice($args, 0, $i);

            //Start compare with / (because no exact match found)
            $page = $className::findByField($app['em'], 'url', '/' . implode('/', $parts) . '/');
            if (!$page) {
                $page = $className::findByField($app['em'], 'url', '/' . implode('/', $parts));
            }

            if ($page) {
                $page->args = array_slice($args, $i);
                break;
            }
        }

        if ($page) {
            if ($page->type == 2) {
                return $app->redirect($page->redirectTo);
            }
            return $app['twig']->render($page->objTemplate()->filename, array(
                'pageBuilder' => $page,
                'params' => $page->args,
            ));
        } else {
            return $app->abort(404);
        }
    }

    public function getPageClass() {
        return $app['pageClass'];
    }
}