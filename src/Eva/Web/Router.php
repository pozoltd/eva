<?php

namespace Eva\Web;

use Eva\Router\Node;
use Eva\Router\Router;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class Router extends Router
{
    protected function getNodes()
    {
        $nodes = array();
        $pages = \Eva\ORMs\Page::active($this->app['zdb']);
        foreach ($pages as $itm) {
            $nodes[] = new Node($itm->id, -1, $itm->__rank, $itm->__active, $itm->title, 'home.twig', $itm->url, null, $itm->allowExtra, $itm->maxParams);
        }
        return $nodes;
    }
}