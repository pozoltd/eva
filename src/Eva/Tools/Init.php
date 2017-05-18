<?php
namespace Eva\Tools;

use Eva\Db\Table;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;

class Init implements ControllerProviderInterface
{
    const TEST_MODE = 1;

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $controllers->match('/init', array($this, 'setup'));
        return $controllers;
    }

    public function setup(Application $app, Request $request)
    {
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
        $this->addTable($pdo, '__models', array_merge($TBL_META, $TBL_MODELS));
        $this->addTable($pdo, '__contents', array_merge($TBL_META, $TBL_CONTENTS));
        return new Response('OK');
    }


    public function models(Application $app, Request $request)
    {
        $path = __DIR__ . '/../Files/models/';
        $files = scandir($path);
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            $json = json_decode(file_get_contents($path . $file));
            if (!$json) {
                $app->abort(500, "Not a json file: $file");
            }
            $model = ModelIO::createModel($app, $json);
            if (self::TEST_MODE) {
                ModelIO::generateInitOrmFile($model);
            }
        }
        return new Response('OK');
    }

    public function data(Application $app, Request $request)
    {
        $orm = \Eva\ORMs\User::getByTitle($app['zdb'], 'admin');
        if (!$orm) {
            $encoder = new MessageDigestPasswordEncoder();
            $orm = new \Eva\ORMs\User($app['zdb']);
            $orm->title = 'admin';
            $orm->password = $encoder->encodePassword('20120628', '');
            $orm->email = 'weida@hsh.co.nz';
            $orm->save();
        }

        $orm = \Eva\ORMs\AssetSize::getByTitle($app['zdb'], 'cms_file_preview');
        if (!$orm) {
            $orm = new \Eva\ORMs\AssetSize($app['zdb']);
            $orm->title = 'cms_file_preview';
            $orm->width = 100;
            $orm->save();
        }

        $orm = \Eva\ORMs\RelationshipTag::getByTitle($app['zdb'], 'Page content');
        if (!$orm) {
            $orm = new \Eva\ORMs\RelationshipTag($app['zdb']);
            $orm->title = 'Page content';
            $orm->save();
        }
        return new Response('OK');
    }

    private function addTable($pdo, $tableName, $tableColumns)
    {
        $table = new Table($pdo, $tableName);
        if (self::TEST_MODE) {
            $table->drop();
        }
        $table->create();
        foreach ($tableColumns as $idx => $itm) {
            $table->addColumn($idx, $itm);
        }
        $table->addIndex('CLASS', '__modelClass');
        return $table;
    }
}