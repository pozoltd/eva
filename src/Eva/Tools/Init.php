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
        $path = __DIR__ . '/../../../files/models/';
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

        $orm = \Eva\ORMs\ContentBlock::getByTitle($app['zdb'], 'Header + content');
        if (!$orm) {
            $tag = \Eva\ORMs\RelationshipTag::getByTitle($app['zdb'], 'Page content');

            $orm = new \Eva\ORMs\ContentBlock($app['zdb']);
            $orm->title = 'Header + content';
            $orm->twig = 'header-content.twig';
            $orm->tags = "[\"$tag->id\"]";
            $orm->items = '[{"widget":"0","id":"text","title":"Text:","sql":""},{"widget":"1","id":"textarea","title":"Textarea:","sql":""},{"widget":"2","id":"assetPicker","title":"Asset picker:","sql":""},{"widget":"3","id":"assetFolderPicker","title":"Asset folder picker:","sql":""},{"widget":"4","id":"checkbox","title":"Check box:","sql":""},{"widget":"5","id":"wysiwyg","title":"Wysiwyg:","sql":""},{"widget":"6","id":"date","title":"Date:","sql":""},{"widget":"7","id":"dateTime","title":"Date time:","sql":""},{"widget":"8","id":"time","title":"Time:","sql":""},{"widget":"9","id":"choice","title":"Choice:","sql":"SELECT t1.id AS `key`, t1.title AS value FROM __contents AS t1 LEFT JOIN __models AS t2 ON  t1.modelId = t2.id WHERE t2.className = \'Page\' ORDER BY t1.rank\n"},{"widget":"10","id":"choiceMultiJson","title":"Choice multi json:","sql":"SELECT t1.id AS `key`, t1.title AS value FROM __contents AS t1 LEFT JOIN __models AS t2 ON  t1.modelId = t2.id WHERE t2.className = \'Page\' ORDER BY t1.rank\n"},{"widget":"11","id":"placeholder","title":"Place holder:","sql":""}]';
            $orm->save();
        }

        $orm = \Eva\ORMs\PageCategory::getByTitle($app['zdb'], 'Main nav');
        if (!$orm) {
            $orm = new \Eva\ORMs\PageCategory($app['zdb']);
            $orm->title = 'Main nav';
            $orm->code = 'main';
            $orm->save();
        }

        $orm = \Eva\ORMs\PageCategory::getByTitle($app['zdb'], 'Footer nav');
        if (!$orm) {
            $orm = new \Eva\ORMs\PageCategory($app['zdb']);
            $orm->title = 'Footer nav';
            $orm->code = 'footer';
            $orm->save();
        }

        $orm = \Eva\ORMs\PageTemplate::getByTitle($app['zdb'], 'home.twig');
        if (!$orm) {
            $orm = new \Eva\ORMs\PageTemplate($app['zdb']);
            $orm->title = 'home.twig';
            $orm->filename = 'home.twig';
            $orm->save();
        }

        $orm = \Eva\ORMs\Page::getByTitle($app['zdb'], 'Home');
        if (!$orm) {
            $tmpl = \Eva\ORMs\PageTemplate::getByTitle($app['zdb'], 'home.twig');
            $cat = \Eva\ORMs\PageCategory::getByTitle($app['zdb'], 'Main nav');

            $orm = new \Eva\ORMs\Page($app['zdb']);
            $orm->title = 'Home';
            $orm->type = 1;

            $orm->template = $tmpl->id;
            $orm->category = "[\"$cat->id\"]";
            $orm->url = '/';
//            $orm->categoryRank = 1;
//            $orm->categoryParent = 1;
            $orm->save();
        }

        $orm = \Eva\ORMs\Asset::getByTitle($app['zdb'], 'Pages');
        if (!$orm) {
            $orm = new \Eva\ORMs\Asset($app['zdb']);
            $orm->title = 'Pages';
            $orm->__parentId = -1;
            $orm->isFolder = 1;
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