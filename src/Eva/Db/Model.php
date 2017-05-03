<?php

namespace Eva\Db;

class Model extends ORM  {

    public function getFieldMap() {
        global $TBL_META;
        return array_merge(array(
            'title' => 'title',
            'className' => 'className',
            'namespace' => 'namespace',
            'dataTable' => 'dataTable',
            'modelType' => 'modelType',
            'dataType' => 'dataType',
            'listType' => 'listType',
            'numberPerPage' => 'numberPerPage',
            'defaultSortBy' => 'defaultSortBy',
            'defaultOrder' => 'defaultOrder',
            'columnsJson' => 'columnsJson',
        ), array_combine(array_keys($TBL_META), array_keys($TBL_META)), array(
            'id' => 'id',
            'track' => 'track',
        ));
    }

    public function getTable() {
        return '__models';
    }
}