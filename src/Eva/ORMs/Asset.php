<?php

/**
 * 2017-05-03 17:46:55
 */
namespace Eva\ORMs;

class Asset extends \Eva\Db\ORM {

    function getFieldMap() {
        global $TBL_META;
        return array_merge(array(
            'title' => 'title', 
			'description' => 'description', 
			'isFolder' => 'extra1', 
			'fileName' => 'extra2', 
			'fileType' => 'extra4', 
			'fileSize' => 'extra5', 
			'fileLocation' => 'extra6', 
        ), array_combine(array_keys($TBL_META), array_keys($TBL_META)), array(
            'id' => 'id',
            'track' => 'track',
        ));
    }

    function getTable() {
        return '__contents';
    }

    
}