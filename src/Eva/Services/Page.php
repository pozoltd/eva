<?php
namespace Eva\Services;

use Eva\Route\Node;
use Eva\Route\Tree;
use Eva\Route\Nav;
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

    public function nodes($cat) {
        $nodes = array();
        $data = \Eva\ORMs\Page::data($this->app['zdb']);
        foreach ($data as $itm) {
            $itm->categoryRank = ((empty($itm->categoryRank) || !$itm->categoryRank)) ? array() : (array)json_decode($itm->categoryRank);
            $itm->categoryParent = ((empty($itm->categoryParent) || !$itm->categoryParent)) ? array() : (array)json_decode($itm->categoryParent);
            $itm->category = ((empty($itm->category) || !$itm->category)) ? array() : (array)json_decode($itm->category);

            $itm->rank = isset($itm->categoryRank['cat' . $cat]) ? $itm->categoryRank['cat' . $cat] : 0;
            $itm->parentId = isset($itm->categoryParent['cat' . $cat]) ? $itm->categoryParent['cat' . $cat] : 0;
            if ($cat == -1 && count($itm->category) == 0) {
                $nodes[] = $itm;
            } else if (in_array($cat, $itm->category)) {
                $nodes[] = $itm;
            }
        }
        return $nodes;
    }

    public function nav($cat)
    {
        return new Nav($this->nodes($cat));
    }

}
