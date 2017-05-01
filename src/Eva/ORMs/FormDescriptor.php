<?php

/**
 * 2017-04-30 18:55:53
 */
namespace Eva\ORMs;

class FormDescriptor extends \Eva\Db\ORM {

    function getFieldMap() {
        global $TBL_META;
        return array_merge(array(
            'title' => 'title', 
			'code' => 'extra4', 
			'from' => 'extra3', 
			'recipients' => 'extra1', 
			'fields' => 'content', 
			'thankyouMessage' => 'shortdescription', 
			'antiSpam' => 'extra2', 
        ), array_combine(array_keys($TBL_META), array_keys($TBL_META)), array(
            'id' => 'id',
            'code' => 'code',
        ));
    }

    function getTable() {
        return '__contents';
    }

    
	public function getAntiSpam() {
		return $this->antiSpam == 1 ? true : false;
	}

}