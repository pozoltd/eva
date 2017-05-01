<?php
namespace Pz\Services;

use Silex\ServiceProviderInterface;
use Silex\Application;

class Generic implements ServiceProviderInterface
{

    public function register(Application $app)
    {
        $app['generic'] = $this;
    }

    public function boot(Application $app)
    {
        $this->app = $app;
    }

    public function getModels()
    {
        $repo = $this->app['em']->getRepository('\Pz\Entities\Model');
        return $repo->findAll();
    }
}
