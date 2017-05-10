<?php

namespace Eva\Controllers;

use Eva\Db\Table;
use Eva\Route\URL;
use Eva\Tools\ModelIO;
use Eva\Tools\Utils;
use Silex\Application;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class File extends Pz
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $controllers->match('/', array($this, 'files'));
        $controllers->match('/upload/', array($this, 'upload'))->bind('upload-assets');
        $controllers->match('/{folderId}/', array($this, 'files'));
        return $controllers;
    }

    public function files(Application $app, Request $request, $folderId = null)
    {
        $options = parent::getOptionsFromUrl();
        $options['folderId'] = $folderId;
        return $app['twig']->render($options['page']->twig, $options);
    }

    public function upload(Application $app, Request $request)
    {
        $files = $request->files->get('files');
        if ($files && is_array($files) && count($files) > 0) {
            $originalName = $files[0]->getClientOriginalName();
            $ext = pathinfo($originalName, PATHINFO_EXTENSION);

            $orm = new \Eva\ORMs\Asset($app['zdb']);
            $orm->isFolder = 0;
            $orm->__parentId = $request->request->get('parentId');
            $orm->title = $originalName;
            $orm->description = '';
            $orm->fileName = $originalName;
            $orm->save();

            require_once CMS . '/vendor/blueimp/jquery-file-upload/server/php/UploadHandler.php';
            $uploader = new \UploadHandler(array(
                'upload_dir' => dirname($_SERVER['SCRIPT_FILENAME']) . '/../uploads/',
                'image_versions' => array()
            ), false);
            $_SERVER['HTTP_CONTENT_DISPOSITION'] = $orm->id;
            $result = $uploader->post(false);

            $orm->fileLocation = $orm->id . '.' . $ext;
            $orm->fileType = $result['files'][0]->type;
            $orm->fileSize = $result['files'][0]->size;
            $orm->save();

            if (file_exists(dirname($_SERVER['SCRIPT_FILENAME']) . '/../uploads/' . $result['files'][0]->name)) {
                rename(dirname($_SERVER['SCRIPT_FILENAME']) . '/../uploads/' . $result['files'][0]->name, dirname($_SERVER['SCRIPT_FILENAME']) . '/../uploads/' . $orm->id . '.' . $ext);
            }
            return new Response(json_encode($orm));
        }
        return new Response(json_encode(array(
            'failed'
        )));
    }

}