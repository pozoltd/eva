<?php
namespace Eva\Services;

use Eva\Route\Node;
use Eva\Route\Tree;
use Eva\Route\Nav;
use Eva\Tools\Utils;
use Silex\ServiceProviderInterface;
use Silex\Application;

class Cms implements ServiceProviderInterface
{
    private $nodes = array();

    public function register(Application $app)
    {
        $app['cms'] = $this;
    }

    public function boot(Application $app)
    {
        $this->app = $app;

        $pages = new Node('pages', null, 10, 1, 'Pages', 'pages.twig', '/pz/pages/', 'fa fa-sitemap');
        $database = new Node('database', null, 20, 1, 'Database', null, '#', 'fa fa-database');
        $files = new Node('files', null, 30, 1, 'Files', 'files.twig', '/pz/files/', 'fa fa-file-image-o');
        $admin = new Node('admin', null, 40, 1, 'Admin', null, '#', 'fa fa-cogs');
        $customs = new Node('customs', 'admin', 1000, 1, 'Customised models', 'models.twig', '/pz/models/0/');
        $builtins = new Node('builtins', 'admin', 1100, 1, 'Built-in models', 'models.twig', '/pz/models/1/');

        $model = \Eva\Db\Model::getORMByField($this->app['zdb'], 'className', 'Page');
        $page = new Node(uniqid(), 'pages', 1, 0, 'Pages detail', 'content.twig', "/pz/contents/content/$model->id/");
        $custom = new Node(uniqid(), 'customs', 1000, 0, 'Customised models detail', 'model.twig', '/pz/models/detail/0/');
        $builtin = new Node(uniqid(), 'builtins', 1000, 0, 'Customised models detail', 'model.twig', '/pz/models/detail/1/');

        $this->nodes = array($pages, $page, $database, $files, $admin, $customs, $custom, $builtins, $builtin);

        $data = \Eva\Db\Model::data($this->app['zdb'], array(
            'whereSql' => 'm.dataType != 2',
        ));
        foreach ($data as $itm) {
            $this->nodes[] = new Node($itm->id, $itm->dataType == 0 ? 'database' : 'admin', $itm->__rank, 1, $itm->title, 'contents.twig', "/pz/contents/$itm->id/");
            $this->nodes[] = new Node(uniqid(), $itm->id, 1, 0, $itm->title . ' detail', 'content.twig', "/pz/contents/content/$itm->id/");
        }
    }

    public function nodes() {
        return $this->nodes;
    }

    public function nav()
    {
        return new Nav($this->nodes());
    }

}
