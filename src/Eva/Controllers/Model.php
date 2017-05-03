<?php

namespace Eva\Controllers;

use Eva\Db\Table;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Pz\Common\Utils;

class Model implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $controllers->match('/', array($this, 'models'));
        $controllers->match('/detail/{modelType}/', array($this, 'model'))->bind('add-model');
        $controllers->match('/detail/{modelType}/{id}/', array($this, 'model'))->bind('edit-model');
        $controllers->match('/sort/', array($this, 'sort'))->bind('sort-models');
        $controllers->match('/{modelType}/', array($this, 'models'));
        return $controllers;
    }

    public function models(Application $app, Request $request, $modelType = 0)
    {
        $returnURL = $request->get('$returnURL') ?: '/pz/models/';
        $models = \Eva\ORMs\Model::data($app['zdb'], array(
            'whereSql' => 'm.modelType = ?',
            'params' => array($modelType),
//            'debug' => 1,
        ));
        return $app['twig']->render("models.twig", array(
            'models' => $models,
            'modelType' => $modelType,
            'returnURL' => $returnURL,
        ));
    }

    public function model(Application $app, Request $request, $modelType, $id = null)
    {
        $returnURL = $request->get('$returnURL') ?: '/pz/models/';
        if ($id) {
            $model = \Eva\ORMs\Model::getById($app['zdb'], $id);
            if (!$model) {
                $app->abort(404);
            }
        } else {
            $model = new \Eva\ORMs\Model($app['zdb']);
            $model->label = 'New models';
            $model->className = 'NewModel';
            $model->namespace = DEFAULT_NAMESPACE . '\\ORMs';
            $model->dataTable = '__contents';
            $model->modelType = $modelType;
            $model->dataType = 0;
            $model->listType = 0;
            $model->numberPerPage = 25;
            $model->defaultSortBy = 'id';
            $model->defaultOrder = 1;
        }

        $zdb = $app['zdb'];
        $pdo = $zdb->getConnection();
        $table = new Table($pdo, $model->dataTable);
//        var_dump($table->getFields());exit;

        global $TBL_META, $CMS_WIDGETS;
        $meta = array_merge(array('id', 'track'), array_keys($TBL_META));
        $fields = array_diff($table->getFields(), $meta);
        sort($fields, SORT_NATURAL);
        sort($meta, SORT_NATURAL);
        asort($CMS_WIDGETS, SORT_NATURAL);

        $columns = array_merge($meta, $fields);
        $formBuilder = $app['form.factory']->createBuilder(new \Eva\Forms\Model(), $model, array(
            'defaultSortByOptions' => array_combine($columns, $columns),
        ));
        $form = $formBuilder->getForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $model->save();
            if ($model->modelType == 0) {
                $generated = HOME_DIR . '/src/' . $model->namespace . '/Generated/' . $model->className . '.php';
                $customised = HOME_DIR . '/src/' . $model->namespace . '/' . $model->className . '.php';

                $columnsJson = json_decode($model->columnsJson);
                $mappings = array_map(function ($value) {
                    return "'{$value->field}' => '{$value->column}', ";
                }, $columnsJson);

                $extras = array_map(function ($value) {
                    if ($value->widget == 'checkbox') {
                        $txt = "\n\tpublic function get" . ucfirst($value->field) . "() {\n";
                        $txt .= "\t\treturn \$this->{$value->field} == 1 ? true : false;";
                        $txt .= "\n\t}\n";
                        return $txt;
                    }
                }, $columnsJson);
                $extras = array_filter($extras);

                $str = file_get_contents(__DIR__ . '/../Db/files/generated.txt');
                $str = str_replace('{TIMESTAMP}', date('Y-m-d H:i:s'), $str);
                $str = str_replace('{NAMESPACE}', $model->namespace, $str);
                $str = str_replace('{CLASSNAME}', $model->className, $str);
                $str = str_replace('{DATATABLE}', $model->dataTable, $str);
                $str = str_replace('{MAPPING}', join("\n\t\t\t", $mappings), $str);
                $str = str_replace('{EXTRAS}', count($extras) == 0 ? '' : join("\n\t\t\t", $extras), $str);
                $dir = dirname($generated);
                if (!file_exists($dir)) {
                    mkdir($dir, 0777, true);
                }
                file_put_contents($generated, $str);

                if (!file_exists($customised)) {
                    $str = file_get_contents(__DIR__ . '/../Db/files/customised.txt');
                    $str = str_replace('{TIMESTAMP}', date('Y-m-d H:i:s'), $str);
                    $str = str_replace('{NAMESPACE}', $model->namespace, $str);
                    $str = str_replace('{CLASSNAME}', $model->className, $str);
                    file_put_contents($customised, $str);
                }
            }


            if ($request->get('submit') == 'apply') {
                return $app->redirect($app->url('edit-model', array(
                    'modelType' => $model->modelType,
                    'returnURL' => urlencode($returnURL),
                    'id' => $model->id,
                )));
            } else if ($request->get('submit') == 'save') {
                return $app->redirect($returnURL);
            }
        }

        return $app['twig']->render("model.twig", array(
            'form' => $form->createView(),
            'fields' => $fields,
            'metas' => $meta,
            'widgets' => $CMS_WIDGETS,
            'id' => $id,
            'returnURL' => $returnURL,
        ));
    }

    public function sort(Application $app, Request $request)
    {
        $data = json_decode($request->get('data'));
        foreach ($data as $key => $value) {
            $className = $app['modelClass'];
            $model = $className::findById($app['zdb'], $value);
            if ($model) {
                $model->rank = $key;
                $model->save();
            }
        }
        return new Response('OK');
    }

}