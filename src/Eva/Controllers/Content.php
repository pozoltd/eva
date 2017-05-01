<?php

namespace Eva\Controllers;

use Eva\ORMs\Model;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;


class Content implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $controllers->match('/add/{modelId}/{returnURL}/', array($this, 'content'))->bind('add-content');
        $controllers->match('/edit/{modelId}/{returnURL}/{id}/', array($this, 'content'))->bind('edit-content');
        $controllers->match('/copy/{modelId}/{returnURL}/{id}/', array($this, 'copy'))->bind('copy-content');
        $controllers->match('/remove/', array($this, 'remove'))->bind('remove-content');
        $controllers->match('/sort/{modelId}/', array($this, 'sort'))->bind('sort-contents');
        $controllers->match('/nestable/{modelId}/', array($this, 'nestable'))->bind('nestable');
        $controllers->match('/status/', array($this, 'changeStatus'))->bind('change-status');
        $controllers->match('/{modelId}/', array($this, 'contents'))->bind('contents');
        $controllers->match('/{modelId}/{pageNum}/{sort}/{order}/', array($this, 'contents'))->bind('contents-page');
        return $controllers;
    }

    public function contents(Application $app, Request $request, $modelId, $pageNum = null, $sort = null, $order = null)
    {
        $model = Model::getById($app['zdb'], $modelId);
        if (!$model) {
            $app->abort(404);
        }

        $sort = null;
        $order = null;
        $limit = null;
        if ($model->listType == 0) {
            $sort = $sort ?: $model->defaultSortBy;
            $order = $order ?: ($model->defaultOrder == 0 ? 'ASC' : 'DESC');
            $pageNum = $pageNum ?: 1;
            $limit = $model->numberPerPage;
        } else if ($model->listType == 1 || $model->listType == 2) {
            $sort = '__rank';
            $order = 'ASC';
        }


        $daoClass = $model->namespace . '\\' . $model->className;
        $daos = $daoClass::data($app['zdb'], array(
            'sort' => $sort,
            'order' => $order,
            'page' => $pageNum,
            'limit' => $limit,
        ));

        $total = null;
        if ($model->listType == 0) {
            $result = $daoClass::data($app['em'], array(
                'select' => 'COUNT(entity.id) AS total',
                'dao' => false,
            ));
            $total = $result[0]['total'];
        } else if ($model->listType == 2) {
            $root = new \stdClass();
            $root->id = 0;
            $daos = Utils::buildTree($root, $daos);
        }

        return $app['twig']->render("contents.twig", array(
            'model' => $model,
            'contents' => $daos,
            'returnURL' => 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}",
            'pageNum' => $pageNum,
            'limit' => $limit,
            'sort' => $sort,
            'order' => $order,
            'total' => $total,
        ));
    }

    public function content(Application $app, Request $request, $modelId, $returnURL, $id = null)
    {
        $model = Model::getById($app['zdb'], $modelId);
        if (!$model) {
            $app->abort(404);
        }

        $daoClass = $model->namespace . '\\' . $model->className;
        $content = new $daoClass($app['zdb']);
        if ($id) {
            $content = $daoClass::getById($app['zdb'], $id);
            if (!$content) {
                $app->abort(404);
            }
        }

        return $this->_content($app, $request, $modelId, $returnURL, $id, $content, $model);
    }

    public function copy(Application $app, Request $request, $modelId, $returnURL, $id)
    {
        $modelClass = $app['modelClass'];
        $model = $modelClass::findById($app['em'], $modelId);
        if (!$model) {
            $app->abort(404);
        }

        $daoClass = $model->getFullClass();
        $content = $daoClass::findById($app['em'], $id);
        if (!$content) { 
            $app->abort(404);
        }
        $content->id = null;

        return $this->_content($app, $request, $modelId, $returnURL, $id, $content, $model);
    }

    private function _content(Application $app, Request $request, $modelId, $returnURL, $id, $content, $model) {

        $form = $app['form.factory']->createBuilder('form', $content);
        $model->columnsJson = json_decode($model->columnsJson);
        foreach ($model->columnsJson as $itm) {
            $widget = $itm->widget;
            if (strpos($itm->widget, '\\') !== FALSE) {
                $wgtClass = $itm->widget;
                $widget = new $wgtClass();

            }
            $options = array(
                'label' => $itm->label,
            );
            if ($itm->widget == 'choice' || $itm->widget == '\\Pz\\Forms\\Types\\ChoiceMultiJson') {
                $conn = $app['em']->getConnection();
                $stmt = $conn->executeQuery($itm->sql);
                $stmt->execute();
                $choices = array();
                foreach ($stmt->fetchAll() as $key => $val) {
                    $choices[$val['key']] = $val['value'];
                }
                $options['choices'] = $choices;
                $options['empty_data'] = null;
                $options['required'] = false;
                $options['placeholder'] = 'Choose an option...';
            }
            if ($itm->required == 1) {
                $options['constraints'] = array(
                    new Assert\NotBlank(),
                );
            }
            $form->add($itm->field, $widget, $options);

        }
        $form = $form->getForm();
//        Utils::dump($form);exit;

        if ($request->isMethod("POST")) {
            $form->bind($request);
            if ($form->isValid()) {
                $content->save();

                if ($request->request->get('submit') == 'Save') {
                    return $app->redirect(urldecode($returnURL));
                } else {
                    return $app->redirect($app->url('edit-content', array('modelId' => $modelId, 'returnURL' => $returnURL, 'id' => $content->id)));
                }
            }
        }

        return $app['twig']->render("content.twig", array(
            'form' => $form->createView(),
            'model' => $model,
            'content' => $content,
            'returnURL' => urldecode($returnURL),
        ));
    }

    public function remove(Application $app, Request $request)
    {
        $contentId = $request->get('content');
        $modelId = $request->get('model');
        $modelClass = $app['modelClass'];
        $model = $modelClass::findById($app['em'], $modelId);
        $className = $model->getFullClass();
        $content = $className::findById($app['em'], $contentId);
        $content->delete();
        return new Response('OK');

    }

    public function sort(Application $app, Request $request, $modelId) {
        $modelClass = $app['modelClass'];
        $model = $modelClass::findById($app['em'], $modelId);
        $className = $model->getFullClass();
        $data = json_decode($request->get('data'));
        foreach ($data as $idx => $itm) {
            $obj = $className::findById($app['em'], $itm);
            $obj->rank = $idx;
            $obj->save();
        }
        return new Response('OK');
    }

    public function nestable(Application $app, Request $request, $modelId) {
        $modelClass = $app['modelClass'];
        $model = $modelClass::findById($app['em'], $modelId);
        $className =  $model->getFullClass();
        $data = json_decode($request->get('data'));
        foreach ($data as $itm) {
            $obj = $className::findById($app['em'], $itm->id);
            $obj->rank = $itm->rank;
            $obj->parentId = $itm->parentId;
            $obj->save();
        }
        return new Response('OK');
    }

    public function changeStatus(Application $app, Request $request)
    {
        $modelClass = $app['modelClass'];
        $model = $modelClass::findById($app['em'], $request->get('model'));
        if (!$model) {
            $app->abort(404);
        }

        $daoClass = $model->getFullClass();
        $content = $daoClass::findById($app['em'], $request->get('content'));
        if (!$content) {
            $app->abort(404);
        }

        $content->active = $request->get('status');
        $content->save();
        return new Response('OK');
    }
}