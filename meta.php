<?php

$TBL_MODELS = array(
    'title' => "varchar(256) COLLATE utf8_unicode_ci NOT NULL",
    'className' => "varchar(128) COLLATE utf8_unicode_ci NOT NULL",
    'namespace' => "varchar(128) COLLATE utf8_unicode_ci NOT NULL",
    'dataTable' => "varchar(256) COLLATE utf8_unicode_ci NOT NULL",
    'modelType' => "int(11) NOT NULL",
    'dataType' => "int(11) NOT NULL",
    'listType' => "int(11) NOT NULL",
    'numberPerPage' => "int(11) NOT NULL",
    'defaultSortBy' => "varchar(128) COLLATE utf8_unicode_ci NOT NULL",
    'defaultOrder' => "int(11) NOT NULL",
    'columnsJson' => "longtext COLLATE utf8_unicode_ci NOT NULL",
);

$TBL_META = array(
    '__slug' => "varchar(256) COLLATE utf8_unicode_ci NOT NULL",
    '__modelClass' => "varchar(128) COLLATE utf8_unicode_ci NOT NULL",
    '__rank' => "int(11) DEFAULT NULL",
    '__parentId' => "int(11) DEFAULT NULL",
    '__added' => "datetime DEFAULT NULL",
    '__modified' => "datetime DEFAULT NULL",
    '__active' => "int(11) DEFAULT NULL",
);

$TBL_CONTENTS = array(
    'startdate' => "datetime DEFAULT NULL",
    'enddate' => "datetime DEFAULT NULL",
    'date' => "datetime DEFAULT NULL",
    'firstdate' => "datetime DEFAULT NULL",
    'lastdate' => "datetime DEFAULT NULL",
    'date1' => "datetime DEFAULT NULL",
    'date2' => "datetime DEFAULT NULL",
    'date3' => "datetime DEFAULT NULL",
    'date4' => "datetime DEFAULT NULL",
    'date5' => "datetime DEFAULT NULL",
    'date6' => "datetime DEFAULT NULL",
    'date7' => "datetime DEFAULT NULL",
    'date8' => "datetime DEFAULT NULL",
    'date9' => "datetime DEFAULT NULL",
    'date10' => "datetime DEFAULT NULL",
    'date11' => "datetime DEFAULT NULL",
    'date12' => "datetime DEFAULT NULL",
    'date13' => "datetime DEFAULT NULL",
    'date14' => "datetime DEFAULT NULL",
    'date15' => "datetime DEFAULT NULL",
    'title' => "longtext COLLATE utf8_unicode_ci",
    'isactive' => "longtext COLLATE utf8_unicode_ci",
    'subtitle' => "longtext COLLATE utf8_unicode_ci",
    'shortdescription' => "longtext COLLATE utf8_unicode_ci",
    'description' => "longtext COLLATE utf8_unicode_ci",
    'content' => "longtext COLLATE utf8_unicode_ci",
    'category' => "longtext COLLATE utf8_unicode_ci",
    'subcategory' => "longtext COLLATE utf8_unicode_ci",
    'phone' => "longtext COLLATE utf8_unicode_ci",
    'mobile' => "longtext COLLATE utf8_unicode_ci",
    'fax' => "longtext COLLATE utf8_unicode_ci",
    'email' => "longtext COLLATE utf8_unicode_ci",
    'facebook' => "longtext COLLATE utf8_unicode_ci",
    'twitter' => "longtext COLLATE utf8_unicode_ci",
    'pinterest' => "longtext COLLATE utf8_unicode_ci",
    'linkedIn' => "longtext COLLATE utf8_unicode_ci",
    'instagram' => "longtext COLLATE utf8_unicode_ci",
    'qq' => "longtext COLLATE utf8_unicode_ci",
    'weico' => "longtext COLLATE utf8_unicode_ci",
    'address' => "longtext COLLATE utf8_unicode_ci",
    'website' => "longtext COLLATE utf8_unicode_ci",
    'author' => "longtext COLLATE utf8_unicode_ci",
    'authorbio' => "longtext COLLATE utf8_unicode_ci",
    'url' => "longtext COLLATE utf8_unicode_ci",
    'value' => "longtext COLLATE utf8_unicode_ci",
    'image' => "longtext COLLATE utf8_unicode_ci",
    'gallery' => "longtext COLLATE utf8_unicode_ci",
    'thumbnail' => "longtext COLLATE utf8_unicode_ci",
    'lastname' => "longtext COLLATE utf8_unicode_ci",
    'firstname' => "longtext COLLATE utf8_unicode_ci",
    'name' => "longtext COLLATE utf8_unicode_ci",
    'region' => "longtext COLLATE utf8_unicode_ci",
    'destination' => "longtext COLLATE utf8_unicode_ci",
    'excerpts' => "longtext COLLATE utf8_unicode_ci",
    'about' => "longtext COLLATE utf8_unicode_ci",
    'latitude' => "longtext COLLATE utf8_unicode_ci",
    'longitude' => "longtext COLLATE utf8_unicode_ci",
    'price' => "longtext COLLATE utf8_unicode_ci",
    'saleprice' => "longtext COLLATE utf8_unicode_ci",
    'features' => "longtext COLLATE utf8_unicode_ci",
    'account' => "longtext COLLATE utf8_unicode_ci",
    'username' => "longtext COLLATE utf8_unicode_ci",
    'password' => "longtext COLLATE utf8_unicode_ci",
    'extra1' => "longtext COLLATE utf8_unicode_ci",
    'extra2' => "longtext COLLATE utf8_unicode_ci",
    'extra3' => "longtext COLLATE utf8_unicode_ci",
    'extra4' => "longtext COLLATE utf8_unicode_ci",
    'extra5' => "longtext COLLATE utf8_unicode_ci",
    'extra6' => "longtext COLLATE utf8_unicode_ci",
    'extra7' => "longtext COLLATE utf8_unicode_ci",
    'extra8' => "longtext COLLATE utf8_unicode_ci",
    'extra9' => "longtext COLLATE utf8_unicode_ci",
    'extra10' => "longtext COLLATE utf8_unicode_ci",
    'extra11' => "longtext COLLATE utf8_unicode_ci",
    'extra12' => "longtext COLLATE utf8_unicode_ci",
    'extra13' => "longtext COLLATE utf8_unicode_ci",
    'extra14' => "longtext COLLATE utf8_unicode_ci",
    'extra15' => "longtext COLLATE utf8_unicode_ci",
);

$CMS_WIDGETS = array(
    '\\Eva\\Forms\\Types\\FormBuilder' => '*Form builder',
    '\\Eva\\Forms\\Types\\FormData' => '*Form data',
    '\\Eva\\Forms\\Types\\AssetPicker' => 'Asset picker',
    '\\Eva\\Forms\\Types\\AssetFolderPicker' => 'Asset folder picker',
    '\\Eva\\Forms\\Types\\Blocks' => 'Blocks',
    '\\Eva\\Forms\\Types\\ChoiceMultiJson' => 'Choice Multi JSON',
    '\\Eva\\Forms\\Types\\DatePicker' => 'Date picker',
    '\\Eva\\Forms\\Types\\DateTimePicker' => 'Date time picker',
    '\\Eva\\Forms\\Types\\Wysiwyg' => 'Wysiwyg',
    'checkbox' => 'Checkbox',
    'choice' => 'Choice',
    'email' => 'Email',
    'password' => 'Password',
    'text' => 'Text',
    'textarea' => 'Textarea',
    'hidden' => 'Hidden',
);


$FORM_WIDGETS = array(
    'choice' => 'Choice',
    'checkbox' => 'Checkbox',
    '\\Pz\\Forms\\Types\\DatePicker' => 'Date picker',
    '\\Pz\\Forms\\Types\\DateTimePicker' => 'Date time picker',
    'email' => 'Email',
    'hidden' => 'Hidden',
    'text' => 'Text',
    'textarea' => 'Textarea',
    'repeated' => 'Repeated',
    '\\Pz\\Forms\\Types\\Wysiwyg' => 'Wysiwyg',
    'submit' => 'Submit',
);
?>