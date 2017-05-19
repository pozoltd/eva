<?php
namespace Eva\Controllers;

use Eva\Route\Node;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class Index extends Route
{
    public function route(Application $app, Request $request, $url = null)
    {
        return parent::route($app, $request, $url);
    }

    protected function getNodes() {
        $nodes = array();
        $pages = \Eva\ORMs\Page::active($this->app['zdb']);
        foreach ($pages as $itm) {
            $nodes[] = new Node($itm->id, -1, $itm->__rank, $itm->__active, $itm->title, 'home.twig', $itm->url, null, $itm->allowExtra, $itm->maxParams);
        }
        return $nodes;
    }
}