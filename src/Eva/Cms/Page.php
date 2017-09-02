<?php
namespace Eva\Cms;

use Eva\Db\Model;
use Eva\Router\Nav;
use Eva\Router\Node;
use Eva\Router\URL;
use Eva\Tools\Utils;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class Page extends Router
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $controllers->match('/pages', array($this, 'pages'))->bind('pages');
//		$controllers->match('/remove/', array($this,'remove'))->bind('remove-page');
        $controllers->match('/pages/count', array($this, 'count'))->bind('count-pages');
        $controllers->match('/pages/change', array($this, 'change'))->bind('change-category');
        $controllers->match('/pages/sort', array($this, 'sort'))->bind('sort-pages');
        return $controllers;
    }

    public function pages(Application $app, Request $request) {
        $categories = \Eva\ORMs\PageCategory::data($this->app['zdb']);
        $options = parent::getOptionsFromUrl('/pz/secured/pages');
        $options['cat'] = $this->app['request']->get('cat') ?: (count($categories) > 0 ? $categories[0]->id : -1);

        $nodes = array();
        $result = \Eva\ORMs\Page::data($app['zdb']);
        foreach ($result as $itm) {
            $itm->categoryRank = ((empty($itm->categoryRank) || !$itm->categoryRank)) ? array() : (array)json_decode($itm->categoryRank);
            $itm->categoryParent = ((empty($itm->categoryParent) || !$itm->categoryParent)) ? array() : (array)json_decode($itm->categoryParent);
            $itm->category = ((empty($itm->category) || !$itm->category)) ? array() : (array)json_decode($itm->category);

            $itm->rank = isset($itm->categoryRank['cat' . $options['cat']]) ? $itm->categoryRank['cat' . $options['cat']] : 0;
            $itm->parentId = isset($itm->categoryParent['cat' . $options['cat']]) ? $itm->categoryParent['cat' . $options['cat']] : -1;
            if ($options['cat'] == -1 && count($itm->category) == 0 || in_array($options['cat'], $itm->category)) {
                $node = new Node($itm->id, $itm->parentId, $itm->rank, $itm->__active, $itm->title, $itm->template, $itm->url);
                $node->__modelClass = $itm->__modelClass;
                $nodes[] = $node;
            }
        }
        $nav = new Nav($nodes);
        $options['root'] = $nav->root();
        return $app['twig']->render($options['page']->twig, $options);
    }

//	public function remove(Application $app, Request $request) {
//		$modelName = $request->get('modelName');
//		$repo = $app['zdb']->getRepository('Secret\Entities\Content');
//		$model = $repo->model(urldecode($modelName));
//		$user = $app['user']->getUser();
//		if ((!$user || $user->admin == 0) && $model['permission'] == 1) {
//			return $app->abort(401);
//		}
//
//		$id = $request->request->get('id');
//		$repo = $app['zdb']->getRepository('Secret\Entities\Content');
//		$result = $repo->data('Page', '', array(), array('sort' => 'rank', 'order' => 'ASC'));
//		$root = URL::buildTree(array('id' => 0), $result);
//		$ids = URL::withChildIds($root, $id);
//		foreach ($ids as $itm) {
//			$entity = $repo->find($itm);
//			$app['zdb']->remove($entity);
//		}
//		$app['zdb']->flush();
//		return new Response('Success');
//	}

    public function count(Application $app, Request $request)
    {
        $result = \Eva\ORMs\Page::data($app['zdb']);
        $counter = array();
        foreach ($result as $itm) {
            $itm->category = ((empty($itm->category) || !$itm->category)) ? array() : (array)json_decode($itm->category);
            if (count($itm->category) > 0) {
                foreach ($itm->category as $itm2) {
                    if (isset($counter['cat' . $itm2])) {
                        $counter['cat' . $itm2]++;
                    } else {
                        $counter['cat' . $itm2] = 1;
                    }
                }
            } else {
                if (isset($counter['cat-1'])) {
                    $counter['cat-1']++;
                } else {
                    $counter['cat-1'] = 1;
                }
            }
        }
        return new Response(json_encode($counter));
    }

    public function change(Application $app, Request $request)
    {
        $categories = \Eva\ORMs\PageCategory::data($this->app['zdb']);
        $options = parent::getOptionsFromUrl('/pz/secured/pages');
        $options['cat'] = $this->app['request']->get('cat') ?: (count($categories) > 0 ? $categories[0]->id : -1);

        $nodes = array();
        $result = \Eva\ORMs\Page::data($app['zdb']);
        foreach ($result as $itm) {
            $itm->categoryRank = ((empty($itm->categoryRank) || !$itm->categoryRank)) ? array() : (array)json_decode($itm->categoryRank);
            $itm->categoryParent = ((empty($itm->categoryParent) || !$itm->categoryParent)) ? array() : (array)json_decode($itm->categoryParent);
            $itm->category = ((empty($itm->category) || !$itm->category)) ? array() : (array)json_decode($itm->category);

            $itm->rank = isset($itm->categoryRank['cat' . $options['cat']]) ? $itm->categoryRank['cat' . $options['cat']] : 0;
            $itm->parentId = isset($itm->categoryParent['cat' . $options['cat']]) ? $itm->categoryParent['cat' . $options['cat']] : -1;
            if ($request->get('oldCat') == -1 && count($itm->category) == 0 || in_array($request->get('oldCat'), $itm->category)) {
                $nodes[] = $itm;
            }
        }
        $nav = new Nav($nodes);
        $root = $nav->root();

        $descendants = $root->descendants($request->get('id'));
        foreach ($descendants as $itm) {
            if ($itm->id != $request->get('id')) {
                $itm->categoryRank['cat' . $request->get('newCat')] = $itm->categoryRank['cat' . $request->get('oldCat')];
                $itm->categoryParent['cat' . $request->get('newCat')] = $itm->categoryParent['cat' . $request->get('oldCat')];

                $categories = array();
                foreach ($itm->category as $cat) {
                    if ($request->get('oldCat') != $cat) {
                        $categories[] = $cat;
                    }
                }
                $itm->category = $categories;
                if (!in_array($request->get('newCat'), $itm->category)) {
                    $itm->category[] = $request->get('newCat');
                }

                $itm->categoryRank = json_encode($itm->categoryRank);
                $itm->categoryParent = json_encode($itm->categoryParent);
                $itm->category = json_encode($itm->category);
                $itm->save();
            }
        }

        $orm = \Eva\ORMs\Page::getById($app['zdb'], $request->get('id'));
        if ($orm) {
            $orm->categoryRank = ((empty($orm->categoryRank) || !$orm->categoryRank)) ? array() : (array)json_decode($orm->categoryRank);
            $orm->categoryParent = ((empty($orm->categoryParent) || !$orm->categoryParent)) ? array() : (array)json_decode($orm->categoryParent);
            $orm->category = (empty($orm->category) || !$orm->category) ? array() : json_decode($orm->category);

            $categories = array();
            foreach ($orm->category as $cat) {
                if ($request->get('oldCat') != $cat) {
                    $categories[] = $cat;
                }
            }
            $orm->category = $categories;
            if (!in_array($request->get('newCat'), $orm->category)) {
                $orm->category[] = $request->get('newCat');
            }

            $orm->categoryRank['cat' . $request->get('newCat')] = 0;
            $orm->categoryParent['cat' . $request->get('newCat')] = -1;

            $orm->categoryRank = json_encode($orm->categoryRank);
            $orm->categoryParent = json_encode($orm->categoryParent);
            $orm->category = json_encode($orm->category);
            $orm->save();
        }

        return new Response('OK');
    }


    public function sort(Application $app, Request $request)
    {
        $result = \Eva\ORMs\Page::data($app['zdb']);
        $data = json_decode($request->get('data'));
        foreach ($result as &$itm) {
            $itm->categoryRank = ((empty($itm->categoryRank) || !$itm->categoryRank)) ? array() : (array)json_decode($itm->categoryRank);
            $itm->categoryParent = ((empty($itm->categoryParent) || !$itm->categoryParent)) ? array() : (array)json_decode($itm->categoryParent);
            $itm->category = ((empty($itm->category) || !$itm->category)) ? array() : (array)json_decode($itm->category);
            foreach ($data as $itm2) {
                $itm2 = (object)$itm2;
                if ($itm->id == $itm2->id) {
                    $itm->categoryRank['cat' . $request->get('cat')] = $itm2->rank;
                    $itm->categoryParent['cat' . $request->get('cat')] = $itm2->parentId ?: -1;
                }
            }
            $itm->categoryRank = json_encode($itm->categoryRank);
            $itm->categoryParent = json_encode($itm->categoryParent);
            $itm->category = json_encode($itm->category);
            $itm->save();
        }
        return new Response('OK');
    }
}