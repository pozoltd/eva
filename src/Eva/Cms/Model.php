<?php

namespace Eva\Cms;

use Eva\Db\Table;
use Eva\Router\URL;
use Eva\Tools\ModelIO;
use Eva\Tools\Utils;
use Silex\Application;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class Model extends RouterCms
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $controllers->match('/models', array($this, 'models'));
        $controllers->match('/models/detail/{modelType}', array($this, 'model'))->bind('add-model');
        $controllers->match('/models/detail/{modelType}/{id}', array($this, 'model'))->bind('edit-model');
        $controllers->match('/models/sort', array($this, 'sort'))->bind('sort-models');
        $controllers->match('/models/{modelType}', array($this, 'models'));
        return $controllers;
    }

    public function models(Application $app, Request $request, $modelType = 0)
    {
        $options = parent::getOptionsFromUrl();
        $options['modelType'] = $modelType;
        return $app['twig']->render($options['page']->twig, $options);
    }

    public function model(Application $app, Request $request, $modelType, $id = null)
    {
        $options = parent::getOptionsFromUrl();
        if ($id) {
            $options['model'] = \Eva\Db\Model::getById($app['zdb'], $id);
            if (!$options['model']) {
                $app->abort(404);
            }
        } else {
            $options['model'] = new \Eva\Db\Model($app['zdb']);
            $options['model']->label = 'New models';
            $options['model']->className = 'NewModel';
            $options['model']->namespace = DEFAULT_NAMESPACE . '\\ORMs';
            $options['model']->dataTable = '__contents';
            $options['model']->modelType = $modelType;
            $options['model']->dataType = 0;
            $options['model']->listType = 0;
            $options['model']->numberPerPage = 25;
            $options['model']->defaultSortBy = 'id';
            $options['model']->defaultOrder = 1;
        }

        global $CMS_WIDGETS, $TBL_META;
        $zdb = $app['zdb'];
        $pdo = $zdb->getConnection();
        $table = new Table($pdo, $options['model']->dataTable);
//        var_dump($table->getFields());exit;

        $options['widgets'] = $CMS_WIDGETS;
        $options['metas'] = array_merge(array('id', 'track'), array_keys($TBL_META));
        $options['fields'] = array_diff($table->getFields(), $options['metas']);

        sort($options['metas'], SORT_NATURAL);
        sort($options['fields'], SORT_NATURAL);
        asort($CMS_WIDGETS, SORT_NATURAL);

        $columns = array_merge($options['metas'], $options['fields']);
        $formBuilder = $app['form.factory']->createBuilder(new \Eva\Forms\Model(), $options['model'], array(
            'defaultSortByOptions' => array_combine($columns, $columns),
        ));
        $form = $formBuilder->getForm();
        $options['form'] = $form->createView();

        $form->handleRequest($request);
        if ($form->isValid()) {
            $options['model']->save();
            if ($options['model']->modelType == 0) {
                ModelIO::generateOrmFile($options['model']);
                ModelIO::generateCustomOrmFile($options['model']);
            }

            $options['returnUrl'] = $request->get('returnUrl') ?: '/pz/models/' . $options['model']->modelType;
            if ($request->get('submit') == 'apply') {
                return $app->redirect($app->url('edit-model', array(
                        'modelType' => $options['model']->modelType,
                        'id' => $options['model']->id,
                    )) . '?returnUrl=' . urlencode($options['returnUrl']));

            } else if ($request->get('submit') == 'save') {
                return $app->redirect($options['returnUrl']);
            }
        }
//        Utils::dump($options['returnUrl']);exit;
        return $app['twig']->render($options['page']->twig, $options);
    }

    public function sort(Application $app, Request $request)
    {
        $data = json_decode($request->get('data'));
        foreach ($data as $idx => $itm) {
            $model = \Eva\Db\Model::getById($app['zdb'], $itm);
            if ($model) {
                $model->__rank = $idx;
                $model->save();
            }
        }
        return new Response('OK');
    }

}