<?php

namespace Eva\Router;

use Eva\Tools\Utils;
use Symfony\Component\HttpFoundation\Request;

class URL
{
    public static function getUrl()
    {
        return 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
    }

    public static function getUrlWq()
    {
        $url = static::getUrl();
        return substr($url, 0, strrpos($url, '?') ?: strlen($url));
    }

    public static function buildTree($node, $arr)
    {
        if (!isset($node->_c)) {
            $node->_c = array();
        }
        foreach ($arr as $itm) {
            if ($itm->parentId == $node->id) {
                $node->_c[] = static::buildTree($itm, $arr);
            }
        }
        return $node;
    }

    public static function encodeURL($url)
    {
        return urlencode(urlencode($url));
    }

    public static function withChildIds($node, $id, $status = 0)
    {
        $result = array();
        if ($node->id == $id || $status == 1) {
            $result[] = $node->id;
            $status = 1;
        }
        foreach ($node->_c as $itm) {
            $result = array_merge($result, static::withChildIds($itm, $id, $status));
        }
        return $result;
    }
}