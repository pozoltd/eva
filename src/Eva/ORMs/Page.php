<?php

/**
 * 2017-04-30 18:55:53
 */
namespace Eva\ORMs;

class Page extends \Eva\Db\ORM {

    function getFieldMap() {
        global $TBL_META;
        return array_merge(array(
            'title' => 'title', 
			'type' => 'extra5', 
			'category' => 'category', 
			'url' => 'url', 
			'content' => 'content', 
			'categoryRank' => 'extra2', 
			'categoryParent' => 'extra3', 
			'pageTitle' => 'extra4', 
			'description' => 'description', 
			'redirectTo' => 'extra6', 
			'template' => 'authorbio', 
        ), array_combine(array_keys($TBL_META), array_keys($TBL_META)), array(
            'id' => 'id',
            'code' => 'code',
        ));
    }

    function getTable() {
        return '__contents';
    }

    
}