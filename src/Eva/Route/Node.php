<?php

namespace Eva\Route;

use Eva\Tools\Utils;

class Node
{
    public $id;
    public $parentId;
    public $rank;
    public $visible;
    public $title;
    public $twig;
    public $url;
    public $icon;
    public $children;

    public function __construct($id, $parentId, $rank = null, $visible = 1, $title = null, $twig = null, $url = null, $icon = null, $children = array())
    {
        $this->id = $id;
        $this->parentId = $parentId;
        $this->rank = $rank;
        $this->visible = $visible;
        $this->title = $title;
        $this->twig = $twig;
        $this->url = $url;
        $this->icon = $icon;
        $this->children = $children;
    }

    public function hasChildren()
    {
        $count = 0;
        foreach ($this->children as $itm) {
            if ($itm->visible) {
                $count++;
            }
        }
        return $count;
    }

    public function contains($needle)
    {
//        while (@ob_end_clean());
//        Utils::dump($needle);exit;
        if (!$needle) {
            return 0;
        }
        return static::_contains($this, $needle);
    }

    private static function _contains($node, $needle)
    {
        if ($node->id == $needle->id) {
            return 1;
        }
        foreach ($node->children as $itm) {
            $result = static::_contains($itm, $needle);
            if ($result) {
                return 1;
            }
        }
        return 0;
    }

    public function path($needleId)
    {
        return static::_path($this, $needleId);
    }

    private static function _path($node, $needleId)
    {
        $n = clone $node;
        unset($n->children);
        $result = array($n);

        if ($node->id == $needleId) {
            return $result;
        }
        foreach ($node->children as $itm) {
            $r = static::_path($itm, $needleId);
            if ($r !== false) {
                return array_merge($result, $r);
            }
        }
        return false;
    }
}