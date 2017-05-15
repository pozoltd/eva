<?php

/**
 * 2017-05-15 19:21:43
 */
namespace Eva\ORMs;

class RelationshipTag extends \Eva\Db\ORM {

    function getFieldMap() {
        global $TBL_META;
        return array_merge(array(
            'title' => 'title', 
			'twig' => 'shortdescription', 
			'tags' => 'extra4', 
			'items' => 'extra1', 
        ), array_combine(array_keys($TBL_META), array_keys($TBL_META)), array(
            'id' => 'id',
            'track' => 'track',
        ));
    }

    function getTable() {
        return '__contents';
    }

    
}