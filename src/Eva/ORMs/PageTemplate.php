<?php

/**
 * 2017-05-03 17:07:56
 */
namespace Eva\ORMs;

class PageTemplate extends \Eva\Db\ORM {

    function getFieldMap() {
        global $TBL_META;
        return array_merge(array(
            'title' => 'title', 
			'filename' => 'name', 
        ), array_combine(array_keys($TBL_META), array_keys($TBL_META)), array(
            'id' => 'id',
            'track' => 'track',
        ));
    }

    function getTable() {
        return '__contents';
    }

    
}