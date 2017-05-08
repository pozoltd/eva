<?php
namespace Eva\Services;

use Eva\Route\URL;
use Eva\Tools\Utils;
use Silex\ServiceProviderInterface;
use Silex\Application;

class Get implements ServiceProviderInterface
{

    public function register(Application $app)
    {
        $app['get'] = $this;
    }

    public function boot(Application $app)
    {
        $this->app = $app;
    }

    public function getEncodedURLgetEncodedURL()
    {
        return URL::encodeURL(URL::getUrl());
    }

    public function getUrl()
    {
        return URL::getUrl();
    }

    public function slugify($str)
    {
        return Utils::slugify($str);
    }

    public function getRequestURI() {
        return stripos(URL::getUrl(), '?') === false ? '' : substr(URL::getUrl(), stripos(URL::getUrl(), '?'));
    }

    public function getFormWidgets() {
        global $FORM_WIDGETS;
        return $FORM_WIDGETS;
    }

    public function getFormData($value) {
        if ($value[2] == 'textarea') {
            return nl2br($value[1]);
        } else if ($value[2] == '\Pz\Twig\Types\Wysiwyg') {
            return $value[1];
        }
        return strip_tags($value[1]);
    }
}
