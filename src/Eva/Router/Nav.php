<?php

namespace Eva\Router;

use Eva\Tools\Utils;

class Nav
{
    public $nodes;

    public function __construct($nodes)
    {
        $this->nodes = $nodes;
        usort($this->nodes, function ($elem1, $elem2) {
            return ($elem1->rank - $elem2->rank) > 0 ? 1 : -1;
        });
    }

    public function root($root = null)
    {
        $node = $root ?: new Node(-1, -2);
        return $this->_root($node);
    }

    private function _root($node)
    {
        if (!isset($node->children)) {
            $node->children = array();
        }
        foreach ($this->nodes as $itm) {
            if ($itm->parentId == $node->id) {
                $node->children[] = $this->_root($itm);
            }
        }
        return $node;
    }

    public function getNodeByField($field, $value)
    {
        foreach ($this->nodes as $itm) {
            if ($itm->$field == $value) {
                return $itm;
            }
        }
        return null;
    }

//    public static function withChildIds($node, $id, $status = 0)
//    {
//        $result = array();
//        if ($node->id == $id || $status == 1) {
//            $result[] = $node->id;
//            $status = 1;
//        }
//        foreach ($node->_c as $itm) {
//            $result = array_merge($result, static::withChildIds($itm, $id, $status));
//        }
//        return $result;
//    }
//
//
//    public static function buildTree($node, $arr)
//    {
//        if (!isset($node->_c)) {
//            $node->_c = array();
//        }
//        foreach ($arr as $itm) {
//            if ($itm->parentId == $node->id) {
//                $node->_c[] = static::buildTree($itm, $arr);
//            }
//        }
//        return $node;
//    }
}