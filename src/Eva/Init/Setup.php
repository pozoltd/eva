<?php
namespace Eva\Init;

use Eva\Db\Table;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;

class Setup implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $controllers->match('/', array($this, 'setup'));
        return $controllers;
    }

    public function setup(Application $app, Request $request) {
        $this->tables($app, $request);
        $this->models($app, $request);
        $this->data($app, $request);
        return new Response('OK');
    }

    public function tables(Application $app, Request $request)
    {
        $zdb = $app['zdb'];
        $pdo = $zdb->getConnection();

        global $TBL_META, $TBL_MODELS, $TBL_CONTENTS;

        $table = $this->setTable($pdo, '__models', array_merge($TBL_META, $TBL_MODELS));
        $table->addIndex('CLASS', '__modelClass');

        $table = $this->setTable($pdo, '__contents', array_merge($TBL_META, $TBL_CONTENTS));
        $table->addIndex('CLASS', '__modelClass');
        return new Response('OK');
    }


    public function models(Application $app, Request $request)
    {
        $this->setModel($app, 'Users', 'User');
        $this->setModel($app, 'Page categories', 'PageCategory');
        $this->setModel($app, 'Page templates', 'PageTemplate');
        $this->setModel($app, 'Pages', 'Page');
        $this->setModel($app, 'Asset sizes', 'AssetSize');
        $this->setModel($app, 'Assets', 'Asset');
        $this->setModel($app, 'Form descriptors', 'FormDescriptor');
        $this->setModel($app, 'Form submissions', 'FormSubmission');
        return new Response('OK');
    }

    public function data(Application $app, Request $request)
    {
        $user = \Eva\ORMs\User::getByTitle($app['zdb'], 'admin');
        if (!$user) {
            $encoder = new MessageDigestPasswordEncoder();
            $user = new \Eva\ORMs\User($app['zdb']);
            $user->title = 'admin';
            $user->password = $encoder->encodePassword('20120628', '');
            $user->email = 'weida@hsh.co.nz';
            $user->__active = 1;
            $user->save();
        }
        return new Response('OK');
    }

    private function setTable($pdo, $tableName, $tableColumns) {
        $table = new Table($pdo, $tableName);
        $table->create();
        foreach ($tableColumns as $idx => $itm) {
            $table->addColumn($idx, $itm);
        }
        return $table;
    }

    private function setModel($app, $modelName, $modelClass) {
        $model = \Eva\Db\Model::getORMByField($app['zdb'], 'className', $modelClass);
        if (!$model) {

            $model = new \Eva\Db\Model($app['zdb']);
            $model->title = $modelName;
            $model->className = $modelClass;
            $model->namespace = 'Eva\\ORMs';
            $model->dataTable = '__contents';
            $model->modelType = 1;
            $model->dataType = 0;
            $model->listType = 1;
            $model->numberPerPage = 25;
            $model->defaultSortBy = 'id';
            $model->defaultOrder = 1;

            $columnsJson = json_decode(file_get_contents(__DIR__ . "/jsons/{$model->className}.json"));
            $model->columnsJson = json_encode($columnsJson);
            $model->save();

            $setup = __DIR__ . '/../ORMs/' . $model->className . '.php';
            if (!file_exists($setup)) {
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

                if ($modelClass == 'User') {
                    $str = file_get_contents(__DIR__ . '/../Db/files/user.txt');
                } else {
                    $str = file_get_contents(__DIR__ . '/../Db/files/setup.txt');
                }
                $str = str_replace('{TIMESTAMP}', date('Y-m-d H:i:s'), $str);
                $str = str_replace('{NAMESPACE}', $model->namespace, $str);
                $str = str_replace('{CLASSNAME}', $model->className, $str);
                $str = str_replace('{DATATABLE}', $model->dataTable, $str);
                $str = str_replace('{MAPPING}', join("\n\t\t\t", $mappings), $str);
                $str = str_replace('{EXTRAS}', count($extras) == 0 ? '' : join("\n\t\t\t", $extras), $str);
                $dir = dirname($setup);
                if (!file_exists($dir)) {
                    mkdir($dir, 0777, true);
                }
                file_put_contents($setup, $str);
            }
        }
    }
}