<?php

namespace Eva\Router;

use Eva\Tools\Utils;
use Symfony\Component\HttpFoundation\Request;

class URL
{
    public static function getUrl()
    {
        return 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}" . urldecode($_SERVER['REQUEST_URI']);
    }

    public static function getUrlWq()
    {
        $url = static::getUrl();
        return substr($url, 0, strrpos($url, '?') ?: strlen($url));
    }

    public static function encodeURL($url)
    {
        return urlencode(urlencode($url));
    }

}