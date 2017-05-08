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

        $pages = new Node('pages', null, 10, 1, 'pages.twig', '/pz/pages/', 'Pages', 'fa fa-sitemap');
        $database = new Node('database', null, 20, 1, null, '#', 'Database', 'fa fa-database');
        $files = new Node('files', null, 30, 1, 'pages.twig', '/pz/files/', 'Files', 'fa fa-file-image-o');
        $admin = new Node('admin', null, 40, 1, null, '#', 'Admin', 'fa fa-cogs');
        $customs = new Node('customs', 'admin', 1000, 1, 'models.twig', '/pz/models/0/', 'Customised models');
        $builtins = new Node('builtins', 'admin', 1100, 1, 'models.twig', '/pz/models/1/', 'Built-in models');

        $model = \Eva\Db\Model::getORMByField($this->app['zdb'], 'className', 'Page');
        $page = new Node(uniqid(), 'pages', 1, 0, 'content.twig', "/pz/contents/content/$model->id/", 'Pages detail');
        $custom = new Node(uniqid(), 'customs', 1000, 0, 'model.twig', '/pz/models/detail/0/', 'Customised models detail');
        $builtin = new Node(uniqid(), 'builtins', 1000, 0, 'model.twig', '/pz/models/detail/1/', 'Customised models detail');

        $this->nodes = array($pages, $page, $database, $files, $admin, $customs, $custom, $builtins, $builtin);

        $models = \Eva\Db\Model::data($this->app['zdb'], array(
            'whereSql' => 'm.dataType != 2',
        ));
        foreach ($models as $itm) {
            $this->nodes[] = new Node($itm->id, $itm->dataType == 0 ? 'database' : 'admin', $itm->__rank, 1, 'contents.twig', "/pz/contents/$itm->id/", $itm->title);
            $this->nodes[] = new Node(uniqid(), $itm->id, 1, 0, 'content.twig', "/pz/contents/content/$itm->id/", $itm->title . ' detail');
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
