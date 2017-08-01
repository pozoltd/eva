<?php
namespace Eva\Cms;

use Eva\Router\Node;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class Router extends \Eva\Router\Router
{
    public function route(Application $app, Request $request, $url = null)
    {
        if ($url === 'secured') {
            return $this->app->abort(301, '/pz/secured/pages');
        }
        return parent::route($app, $request, $url);
    }

    protected function getNodes()
    {
        $nodes = array();
        try {
            $pages = new Node('pages', -1, 10, 1, 'Pages', 'pages.twig', '/pz/secured/pages', 'fa fa-sitemap');
            $database = new Node('database', -1, 20, 1, 'Database', null, '#', 'fa fa-database');
            $files = new Node('files', -1, 30, 1, 'Files', 'files.twig', '/pz/secured/files', 'fa fa-file-image-o');
            $admin = new Node('admin', -1, 40, 1, 'Admin', null, '#', 'fa fa-cogs');
            $customs = new Node('customs', 'admin', 1000, 1, 'Customised models', 'models.twig', '/pz/secured/models/0');
            $builtins = new Node('builtins', 'admin', 1100, 1, 'Built-in models', 'models.twig', '/pz/secured/models/1');

            $model = \Eva\Db\Model::getORMByField($this->app['zdb'], 'className', 'Page');
            $page = new Node(uniqid(), 'pages', 1, 0, 'Pages detail', 'content.twig', "/pz/secured/contents/content/$model->className", null, 1, 2);
            $custom = new Node(uniqid(), 'customs', 1000, 0, 'Customised models detail', 'model.twig', '/pz/secured/models/detail/0', null, 1, 1);
            $builtin = new Node(uniqid(), 'builtins', 1000, 0, 'Customised models detail', 'model.twig', '/pz/secured/models/detail/1', null, 1, 1);

            $nodes = array($pages, $page, $database, $files, $admin, $customs, $custom, $builtins, $builtin);

            $data = \Eva\Db\Model::data($this->app['zdb'], array(
                'whereSql' => 'm.dataType != 2',
            ));
            foreach ($data as $itm) {
                $nodes[] = new Node($itm->id, $itm->dataType == 0 ? 'database' : 'admin', $itm->__rank, 1, $itm->title, 'contents.twig', "/pz/secured/contents/$itm->id");
                $twig = 'content.twig';
                if ($itm->className == 'FormDescriptor') {
                    $twig = 'content-form_descriptor.twig';
                }
                $nodes[] = new Node(uniqid(), $itm->id, 1, 0, $itm->title . ' detail', $twig, "/pz/secured/contents/content/$itm->className", null, 1, 2);
            }
        } catch (\PDOException $ex) {}

        return $nodes;
    }
}