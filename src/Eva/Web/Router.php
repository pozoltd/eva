<?php

namespace Eva\Web;

use Eva\ORMs\PageTemplate;
use Eva\Router\Node;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class Router extends \Eva\Router\Router
{
    protected function getNodes()
    {
        $nodes = array();
        $pages = \Eva\ORMs\Page::active($this->app['zdb']);
        foreach ($pages as $itm) {
            $twig = PageTemplate::getById($this->app['zdb'], $itm->template);
            if (!$twig) {
                $this->app->abort(404);
            }
            $nodes[] = new Node($itm->id, -1, $itm->__rank, $itm->__active, $itm->title, $twig->filename, $itm->url, null, $itm->allowExtra, $itm->maxParams);
        }
        return $nodes;
    }
}