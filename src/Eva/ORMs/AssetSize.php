<?php

/**
 * 2017-04-30 18:55:53
 */
namespace Eva\ORMs;

class AssetSize extends \Eva\Db\ORM {

    function getFieldMap() {
        global $TBL_META;
        return array_merge(array(
            'title' => 'title', 
			'width' => 'extra1', 
			'description' => 'description', 
        ), array_combine(array_keys($TBL_META), array_keys($TBL_META)), array(
            'id' => 'id',
            'code' => 'code',
        ));
    }

    function getTable() {
        return '__contents';
    }

    
}