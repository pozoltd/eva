<?php
namespace Eva\Tools;

use Silex\Application;

class ModelIO
{

    public static function createModel(Application $app, $json)
    {
        $model = \Eva\Db\Model::getORMByField($app['zdb'], 'className', $json->className);
        if (!$model) {
            $model = new \Eva\Db\Model($app['zdb']);
            foreach ($model->getFieldMap() as $idx => $itm) {
                if ($idx == 'columnsJson') {
                    $model->columnsJson = json_encode($json->columnsJson);
                } elseif (isset($json->$idx)) {
                    $model->$idx = $json->$idx;
                }
            }
            $model->save();
        }
        return $model;
    }

    public static function generateInitOrmFile($model)
    {
        $ormFile = __DIR__ . '/../ORMs/' . $model->className . '.php';
        if (!file_exists($ormFile)) {
            $mappings = array_map(function ($value) {
                return "'{$value->field}' => '{$value->column}', ";
            }, json_decode($model->columnsJson));

            $extras = array_map(function ($value) {
                if ($value->widget == 'checkbox') {
                    $txt = "\n\tpublic function get" . ucfirst($value->field) . "() {\n";
                    $txt .= "\t\treturn \$this->{$value->field} == 1 ? true : false;";
                    $txt .= "\n\t}\n";
                    return $txt;
                }
            }, json_decode($model->columnsJson));
            $extras = array_filter($extras);

            if ($model->className == 'User') {
                $str = file_get_contents(__DIR__ . '/../../../files/orms/orm_user.txt');
            } else {
                $str = file_get_contents(__DIR__ . '/../../../files/orms/orm.txt');
            }
            $str = str_replace('{TIMESTAMP}', date('Y-m-d H:i:s'), $str);
            $str = str_replace('{NAMESPACE}', $model->namespace, $str);
            $str = str_replace('{CLASSNAME}', $model->className, $str);
            $str = str_replace('{DATATABLE}', $model->dataTable, $str);
            $str = str_replace('{MAPPING}', join("\n\t\t\t", $mappings), $str);
            $str = str_replace('{EXTRAS}', count($extras) == 0 ? '' : join("\n\t\t\t", $extras), $str);
            $dir = dirname($ormFile);
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($ormFile, $str);
        }
    }

    public static function generateOrmFile($model)
    {
        $file = HOME_DIR . '/src/' . $model->namespace . '/Generated/' . $model->className . '.php';

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

        $str = file_get_contents(__DIR__ . '/../../../files/orms/orm.txt');
        $str = str_replace('{TIMESTAMP}', date('Y-m-d H:i:s'), $str);
        $str = str_replace('{NAMESPACE}', $model->namespace . '\\Generated', $str);
        $str = str_replace('{CLASSNAME}', $model->className, $str);
        $str = str_replace('{DATATABLE}', $model->dataTable, $str);
        $str = str_replace('{MAPPING}', join("\n\t\t\t", $mappings), $str);
        $str = str_replace('{EXTRAS}', count($extras) == 0 ? '' : join("\n\t\t\t", $extras), $str);
        $dir = dirname($file);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($file, $str);
    }

    public static function generateCustomOrmFile($model)
    {
        $file = HOME_DIR . '/src/' . $model->namespace . '/' . $model->className . '.php';

        if (!file_exists($file)) {
            $str = file_get_contents(__DIR__ . '/../../../files/orms/orm_custom.txt');
            $str = str_replace('{TIMESTAMP}', date('Y-m-d H:i:s'), $str);
            $str = str_replace('{NAMESPACE}', $model->namespace, $str);
            $str = str_replace('{CLASSNAME}', $model->className, $str);
            file_put_contents($file, $str);
        }
    }
}