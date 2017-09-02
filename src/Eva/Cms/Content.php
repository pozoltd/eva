<?php

namespace Eva\Cms;

use Eva\Db\Model;
use Eva\Tools\Utils;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class Content extends Router
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

//        $controllers->match('/{modelId}/{pageNum}/{sort}/{order}', array($this, 'contents'))->bind('contents-page');
        $controllers->match('/contents/content/{modelClass}/add', array($this, 'content'))->bind('add-content');
        $controllers->match('/contents/content/{modelClass}/{id}/edit', array($this, 'content'))->bind('edit-content');
        $controllers->match('/contents/content/{modelClass}/{id}/copy', array($this, 'copy'))->bind('copy-content');

        $controllers->match('/contents/remove', array($this, 'remove'))->bind('remove-content');
        $controllers->match('/contents/sort', array($this, 'sort'))->bind('sort-contents');
        $controllers->match('/contents/status', array($this, 'changeStatus'))->bind('change-status');
        $controllers->match('/contents/nestable', array($this, 'nestable'))->bind('nestable');

        $controllers->match('/contents/{modelId}', array($this, 'contents'))->bind('contents');
        return $controllers;
    }

    public function contents(Application $app, Request $request, $modelId, $pageNum = null, $sort = null, $order = null)
    {
        $options = parent::getOptionsFromUrl();

        $options['model'] = Model::getById($app['zdb'], $modelId);
        if (!$options['model']) {
            $app->abort(404);
        }

        $sort = null;
        $order = null;
        $limit = null;
        if ($options['model']->listType == 0) {
            $sort = $sort ?: $options['model']->defaultSortBy;
            $order = $order ?: ($options['model']->defaultOrder == 0 ? 'ASC' : 'DESC');
            $pageNum = $pageNum ?: 1;
            $limit = $options['model']->numberPerPage;
        } else if ($options['model']->listType == 1 || $options['model']->listType == 2) {
            $sort = '__rank';
            $order = 'ASC';
        }


        $ormClass = $options['model']->namespace . '\\' . $options['model']->className;
        $options['contents'] = $ormClass::data($app['zdb'], array(
            'sort' => $sort,
            'order' => $order,
            'page' => $pageNum,
            'limit' => $limit,
//            'debug' => 1,
        ));

        return $app['twig']->render($options['page']->twig, $options);
    }

    public function content(Application $app, Request $request, $modelClass, $id = null)
    {
        $options = parent::getOptionsFromUrl();

        $options['model'] = Model::getORMByField($app['zdb'], 'className', $modelClass);
        if (!$options['model']) {
            $app->abort(404);
        }

        $ormClass = $options['model']->namespace . '\\' . $options['model']->className;
        $options['content'] = new $ormClass($app['zdb']);
        if ($id) {
            $options['content'] = $ormClass::getById($app['zdb'], $id);
            if (!$options['content']) {
                $app->abort(404);
            }
        }
//Utils::dump($options['page']);exit;
        $options['form'] = $this->getForm($request, $options);
        return $app['twig']->render($options['page']->twig, $options);

//        return $this->getForm($app, $request, $modelId, $id, $content, $model);
    }

    public function copy(Application $app, Request $request, $modelClass, $id)
    {
        $options = parent::getOptionsFromUrl();

        $options['model'] = Model::getORMByField($app['zdb'], 'className', $modelClass);
        if (!$options['model']) {
            $app->abort(404);
        }

        $ormClass = $options['model']->namespace . '\\' . $options['model']->className;
        $options['content'] = new $ormClass($app['zdb']);
        if ($id) {
            $options['content'] = $ormClass::getById($app['zdb'], $id);
            if (!$options['content']) {
                $app->abort(404);
            }
        }
        $options['content']->id = null;

        $options['form'] = $this->getForm($request, $options);
        return $app['twig']->render($options['page']->twig, $options);
    }

    private function getForm($request, $options)
    {
        $form = $this->app['form.factory']->createBuilder('form', $options['content']);

        $columnsJson = json_decode($options['model']->columnsJson);
        foreach ($columnsJson as $itm) {
            $widget = $itm->widget;
            if (strpos($itm->widget, '\\') !== FALSE) {
                $wgtClass = $itm->widget;
                $widget = new $wgtClass();
            }
            $opts = array(
                'label' => $itm->label,
            );
            if ($itm->widget == 'choice' || $itm->widget == '\\Eva\\Forms\\Types\\ChoiceMultiJson') {
                $conn = $this->app['zdb']->getConnection();
                $stmt = $conn->prepare($itm->sql);
                $stmt->execute();
                $choices = array();
                foreach ($stmt->fetchAll() as $key => $val) {
                    $choices[$val['key']] = $val['value'];
                }
                $opts['choices'] = $choices;
//                $opts['empty_data'] = null;
                $opts['required'] = false;
//                $opts['placeholder'] = 'Select an option...';
            }
            if ($itm->required == 1) {
                $opts['constraints'] = array(
                    new Assert\NotBlank(),
                );
            }
            $form->add($itm->field, $widget, $opts);
        }
        $form = $form->getForm();

        if ($request->isMethod("POST")) {
            $form->bind($request);
            if ($form->isValid()) {
                if (!$options['content']->id) {
                    $options['content']->__active = 1;
                }
                $options['content']->save();

                if ($request->get('submit') == 'Save') {
                    return $this->app->abort(302, urldecode($options['returnUrl']));
                } else {
                    return $this->app->abort(302, $this->app->url('edit-content', array(
                        'modelClass' => $options['model']->className,
                        'id' => $options['content']->id)) . '?returnUrl=' . urlencode($options['returnUrl'])
                    );
                }
            }
        }

        return $form->createView();
    }

    public function remove(Application $app, Request $request)
    {
        $model = \Eva\Db\Model::getORMByField($app['zdb'], 'className', $request->get('model'));
        $className = $model->namespace . '\\' . $model->className;
        $content = $className::getById($app['zdb'], $request->get('content'));
        $content->delete();
        return new Response('OK');
    }


    public function changeStatus(Application $app, Request $request)
    {
        $model = \Eva\Db\Model::getORMByField($app['zdb'], 'className', $request->get('model'));
        $className = $model->namespace . '\\' . $model->className;
        $content = $className::getById($app['zdb'], $request->get('content'));
        $content->__active = $request->get('status');
        $content->save();
        return new Response('OK');
    }

    public function sort(Application $app, Request $request)
    {
        $modelClass = $request->get('model');
        $model = \Eva\Db\Model::getORMByField($app['zdb'], 'className', $modelClass);
        $className = $model->namespace . '\\' . $model->className;
        $data = json_decode($request->get('data'));
        foreach ($data as $idx => $itm) {
            $obj = $className::getById($app['zdb'], $itm);
            $obj->__rank = $idx;
            $obj->save();
        }
        return new Response('OK');
    }

    public function nestable(Application $app, Request $request)
    {
        $modelClass = $app['modelClass'];
        $model = \Eva\Db\Model::getORMByField($app['zdb'], 'className', $modelClass);
        $className = $model->namespace . '\\' . $model->className;
        $data = json_decode($request->get('data'));
        foreach ($data as $itm) {
            $obj = $className::findById($app['zdb'], $itm->id);
            $obj->__rank = $itm->rank;
            $obj->__parentId = $itm->parentId;
            $obj->save();
        }
        return new Response('OK');
    }
}