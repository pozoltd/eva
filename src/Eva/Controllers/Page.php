<?php
namespace Eva\Controllers;

use Eva\Db\Model;
use Eva\Route\URL;
use Eva\Tools\Utils;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class Page extends Pz
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $controllers->match('/', array($this, 'pages'))->bind('pages');
//		$controllers->match('/remove/', array($this,'remove'))->bind('remove-page');
        $controllers->match('/count/', array($this, 'count'))->bind('count-pages');
        $controllers->match('/change/', array($this, 'change'))->bind('change-category');
        $controllers->match('/sort/', array($this, 'sort'))->bind('sort-pages');
        return $controllers;
    }

    public function pages(Application $app, Request $request) {
        $categories = \Eva\ORMs\PageCategory::data($this->app['zdb']);
        $options = parent::getOptionsFromUrl('pz/pages/');
        $options['cat'] = $this->app['request']->get('cat') ?: (count($categories) > 0 ? $categories[0]->id : -1);
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
        $result = \Eva\ORMs\Page::data($app['zdb']);
        $pages = array();
        foreach ($result as $itm) {
            $itm->categoryRank = ((empty($itm->categoryRank) || !$itm->categoryRank)) ? array() : (array)json_decode($itm->categoryRank);
            $itm->categoryParent = ((empty($itm->categoryParent) || !$itm->categoryParent)) ? array() : (array)json_decode($itm->categoryParent);
            $itm->category = ((empty($itm->category) || !$itm->category)) ? array() : (array)json_decode($itm->category);

            $itm->rank = isset($itm->categoryRank['cat' . $request->get('oldCat')]) ? $itm->categoryRank['cat' . $request->get('oldCat')] : 0;
            $itm->parentId = isset($itm->categoryParent['cat' . $request->get('oldCat')]) ? $itm->categoryParent['cat' . $request->get('oldCat')] : 0;
            if ($request->get('oldCat') == -1 && count($itm->category) == 0) {
                $pages[] = $itm;
            } else if (in_array($request->get('oldCat'), $itm->category)) {
                $pages[] = $itm;
            }
        }
        $root = new \stdClass();
        $root->id = 0;
        $root = URL::buildTree($root, $pages);

        $result = \Eva\ORMs\Page::data($app['zdb']);
        $ids = URL::withChildIds($root, $request->get('id'));
        foreach ($ids as $itm) {
            if ($itm != $request->get('id')) {
                foreach ($result as &$itm2) {
                    $itm2->categoryRank = ((empty($itm2->categoryRank) || !$itm2->categoryRank)) ? array() : (array)json_decode($itm2->categoryRank);
                    $itm2->categoryParent = ((empty($itm2->categoryParent) || !$itm2->categoryParent)) ? array() : (array)json_decode($itm2->categoryParent);
                    $itm2->category = (empty($itm2->category) || !$itm2->category) ? array() : json_decode($itm2->category);

                    if ($itm2->id == $itm) {
                        $itm2->categoryRank['cat' . $request->get('newCat')] = $itm2->categoryRank['cat' . $request->get('oldCat')];
                        $itm2->categoryParent['cat' . $request->get('newCat')] = $itm2->categoryParent['cat' . $request->get('oldCat')];

                        $categories = array();
                        foreach ($itm2->category as $itm3) {
                            if ($request->get('oldCat') != $itm3) {
                                $categories[] = $itm3;
                            }
                        }
                        $itm2->category = $categories;
                        if (!in_array($request->get('newCat'), $itm2->category)) {
                            $itm2->category[] = $request->get('newCat');
                        }
                    }

                    $itm2->categoryRank = json_encode($itm2->categoryRank);
                    $itm2->categoryParent = json_encode($itm2->categoryParent);
                    $itm2->category = json_encode($itm2->category);
                    $itm2->save();
                }
            }
        }

        $result = \Eva\ORMs\Page::getById($app['zdb'], $request->get('id'));
        if ($result) {
            $result->category = (empty($result->category) || !$result->category) ? array() : json_decode($result->category);
            $categories = array();
            foreach ($result->category as $itm) {
                if ($request->get('oldCat') != $itm) {
                    $categories[] = $itm;
                }
            }
            $result->category = $categories;
            if (!in_array($request->get('newCat'), $result->category)) {
                $result->category[] = $request->get('newCat');
            }

            $result->categoryRank = ((empty($result->categoryRank) || !$result->categoryRank)) ? array() : (array)json_decode($result->categoryRank);
            $result->categoryParent = ((empty($result->categoryParent) || !$result->categoryParent)) ? array() : (array)json_decode($result->categoryParent);

            $result->categoryRank['cat' . $request->get('newCat')] = 0;
            $result->categoryParent['cat' . $request->get('newCat')] = 0;

            $result->categoryRank = json_encode($result->categoryRank);
            $result->categoryParent = json_encode($result->categoryParent);
            $result->category = json_encode($result->category);
            $result->save();
        }

        return new Response('OK');
    }


    public function sort(Application $app, Request $request)
    {
        $pageClass = $app['pageClass'];
        $result = $pageClass::data($app['zdb']);
        $data = json_decode($request->get('data'));
        foreach ($result as &$itm) {
            $itm->categoryRank = ((empty($itm->categoryRank) || !$itm->categoryRank)) ? array() : (array)json_decode($itm->categoryRank);
            $itm->categoryParent = ((empty($itm->categoryParent) || !$itm->categoryParent)) ? array() : (array)json_decode($itm->categoryParent);
            $itm->category = ((empty($itm->category) || !$itm->category)) ? array() : (array)json_decode($itm->category);
            foreach ($data as $itm2) {
                $itm2 = (object)$itm2;
                if ($itm->id == $itm2->id) {
                    $itm->categoryRank['cat' . $request->get('cat')] = $itm2->rank;
                    $itm->categoryParent['cat' . $request->get('cat')] = $itm2->parentId;
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