<?php

/**
 * 2017-04-30 18:55:53
 */
namespace Eva\ORMs;

class FormSubmission extends \Eva\Db\ORM {

    function getFieldMap() {
        global $TBL_META;
        return array_merge(array(
            'title' => 'title', 
			'uniqueId' => 'about', 
			'date' => 'date', 
			'from' => 'extra4', 
			'recipients' => 'extra5', 
			'content' => 'content', 
			'emailStatus' => 'extra1', 
			'emailRequest' => 'extra2', 
			'emailResponse' => 'extra3', 
			'formDescriptorId' => 'extra6', 
        ), array_combine(array_keys($TBL_META), array_keys($TBL_META)), array(
            'id' => 'id',
            'code' => 'code',
        ));
    }

    function getTable() {
        return '__contents';
    }

    
}