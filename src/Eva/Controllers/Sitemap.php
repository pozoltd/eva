<?php

namespace Pz\Controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Pz\Common\Utils;

class Sitemap implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $controllers->match('/', array($this, 'xmlSitemap'))->bind('sitemap-xml');
        return $controllers;
    }

    public function xmlSitemap(Application $app, Request $request)
    {
        $sitemap = array();

        $result = \Pz\DAOs\Page::active($app['em'], array());
        $sitemap = array();
        foreach ($result as $itm) {
            $itm->sitemapUrl = $itm->url;
            $sitemap[] = $itm;
        }

        $models = \Pz\DAOs\Model::data($app['em']);
        foreach ($models as $model) {
            $className = '\\Site\\DAOs\\' . $model->className;
            if (method_exists($className, 'isSitemap') && $className::isSitemap()) {
                $result = $className::active($app['em']);
                foreach ($result as $itm) {
                    $sitemap[] = $itm;
                }
            }
        }

//        Utils::dump($sitemap);exit;


        $app['twig.loader.filesystem']->addPath(__DIR__ . '/../../../views/');
        $responseContent = $app['twig']->render('sitemap.xml.twig', array(
            'sitemaps' => array($sitemap)
        ));

        $response = new \Symfony\Component\HttpFoundation\Response($responseContent, 200);

        $contentType = 'text/xml';
        $response->headers->set('Content-Type', $contentType);

        return $response;

    }

}