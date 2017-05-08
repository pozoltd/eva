<?php

namespace Eva\Route;

use Eva\Tools\Utils;

class Node
{
    public $id;
    public $parentId;
    public $rank;
    public $visible;
    public $twig;
    public $url;
    public $title;
    public $icon;
    public $children;

    public function __construct($id, $parentId, $rank = null, $visible = 1, $twig = null, $url = null, $title = null, $icon = null, $children = array())
    {
        $this->id = $id;
        $this->parentId = $parentId;
        $this->rank = $rank;
        $this->visible = $visible;
        $this->twig = $twig;
        $this->url = $url;
        $this->title = $title;
        $this->icon = $icon;
        $this->children = $children;
    }

    public function hasChildren() {
        $count = 0;
        foreach ($this->children as $itm) {
            if ($itm->visible) {
                $count++;
            }
        }
        return $count;
    }

    public function contains($needle) {
//        while (@ob_end_clean());
//        Utils::dump($needle);exit;
        if (!$needle) {
            return 0;
        }
        return static::_contains($this, $needle);
    }

    private static function _contains($node, $needle) {
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

}