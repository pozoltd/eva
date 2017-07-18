<?php
namespace Eva\Services;

use Eva\Router\Node;
use Eva\Router\Tree;
use Eva\Router\Nav;
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
    }

    public function contentBlockWidgets()
    {
        global $CONTNET_BLOCK_WIDGETS;
        return $CONTNET_BLOCK_WIDGETS;
    }
}
