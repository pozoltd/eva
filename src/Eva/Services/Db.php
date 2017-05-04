<?php
namespace Eva\Services;

use Silex\ServiceProviderInterface;
use Silex\Application;

class Db implements ServiceProviderInterface
{

    public function register(Application $app)
    {
        $app['db'] = $this;
    }

    public function boot(Application $app)
    {
        $this->app = $app;
    }

    public function active($className, $options = array())
    {
        if (isset($options['whereSql']) && $options['whereSql']) {
            $options['whereSql'] = $options['whereSql'] . ' AND m.__active = 1';
        }
        return $this->data($this->app['zdb'], $options);
    }

    public function data($className, $options = array())
    {
        if (strpos($className, '\\') === false) {
            $model = \Eva\Db\Model::getORMByField($this->app['zdb'], 'className', $className);
            $className = $model->namespace . '\\' . $model->className;
        }
        return $className::data($this->app['zdb'], $options);
    }

    public function getById($className, $id)
    {
        return $this->getByField($className, 'id', $id);
    }

    public function getBySlug($className, $slug)
    {
        return $this->getByField($className, '__slug', $slug);
    }

    public function getByField($className, $field, $value)
    {
        return $this->data($className, array(
            'whereSql' => 'm.' . $field . ' = ?',
            'params' => array(
                $value,
            ),
            'oneOrNull' => 1,
//            'debug' => 1,
        ));
    }
}
