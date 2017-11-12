<?php

/**
 * 2017-05-16 21:45:33
 */

namespace Eva\ORMs;

class Page extends \Eva\Db\ORM
{

    function getFieldMap()
    {
        global $TBL_META;
        return array_merge(array(
            'title' => 'title',
            'type' => 'extra5',
            'redirectTo' => 'extra6',
            'template' => 'authorbio',
            'category' => 'category',
            'url' => 'url',
            'content' => 'content',
            'categoryRank' => 'extra2',
            'categoryParent' => 'extra3',
            'pageTitle' => 'extra4',
            'description' => 'description',
            'allowExtra' => 'extra1',
            'maxParams' => 'extra7',
        ), array_combine(array_keys($TBL_META), array_keys($TBL_META)), array(
            'id' => 'id',
            'track' => 'track',
        ));
    }

    function getTable()
    {
        return '__contents';
    }


    public function getAllowExtra()
    {
        return $this->allowExtra == 1 ? true : false;
    }

    public function objContent()
    {

        return $this->content ? json_decode($this->content) : array();
    }
}