<?php

namespace Eva\ORMs;

use Eva\Db\ORM;

class Model extends ORM  {

    public function getFieldMap() {
        return array(
            'id' => 'id',
            'track' => 'track',
            'rank' => 'rank',
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
        );
    }

    public function getTable() {
        return '__models';
    }
}