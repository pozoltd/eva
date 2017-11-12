<?php
namespace Eva\Services;

use Eva\ORMs\PageCategory;
use Eva\ORMs\PageTemplate;
use Eva\Router\Nav;
use Eva\Router\Node;
use Eva\Tools\Utils;
use Silex\ServiceProviderInterface;
use Silex\Application;

class Page implements ServiceProviderInterface
{

    public function register(Application $app)
    {
        $app['page'] = $this;
    }

    public function boot(Application $app)
    {
        $this->app = $app;
    }

    public function nav($categoryCode)
    {
        $result = null;
        $category = PageCategory::getORMByField($this->app['zdb'], 'code', $categoryCode);
        if ($category) {
            $pages = \Eva\ORMs\Page::data($this->app['zdb'], array(
                'whereSql' => 'm.category LIKE ? ',
                'params' => array('%"' . $category->id . '"%'),
            ));


            $nodes = array();
            foreach ($pages as $itm) {
                $twig = PageTemplate::getById($this->app['zdb'], $itm->template);
                if (!$twig) {
                    $this->app->abort(404);
                }

                $itm->categoryRank = ((empty($itm->categoryRank) || !$itm->categoryRank)) ? array() : (array)json_decode($itm->categoryRank);
                $rank = isset($itm->categoryRank['cat' . $category->id]) ? $itm->categoryRank['cat' . $category->id] : 0;
                $node = new Node($itm->id, -1, $rank, $itm->__active, $itm->title, $twig->filename, $itm->url, null, $itm->allowExtra, $itm->maxParams);
                $node->objContent = $itm->objContent();
                $nodes[] = $node;
            }

            $nav = new Nav($nodes);
            $result = $nav->root();
        }

        return $result;
    }

}
