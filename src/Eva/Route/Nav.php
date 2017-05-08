<?php

namespace Eva\Route;

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

    public function root() {
        $node = new Node(null, null);
        return $this->_root($node, $this->nodes);
    }

    private function _root($node)
    {
        if (!isset($node->children)) {
            $node->children = array();
        }
        foreach ($this->nodes as $itm) {
            if ($itm->parentId == $node->id) {
                $node->children[] = $this->_root($itm, $this->nodes);
            }
        }
        return $node;
    }

    public function getNodeByField($field, $value) {
        foreach ($this->nodes as $itm) {
            if ($itm->$field == $value) {
                return $itm;
            }
        }
        return null;
    }
}