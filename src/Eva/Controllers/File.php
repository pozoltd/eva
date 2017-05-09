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

            $newFile = new \Eva\ORMs\Asset($app['zdb']);
            $newFile->isFolder = 0;
            $newFile->__parentId = $request->request->get('parentId');
            $newFile->title = $originalName;
            $newFile->description = '';
            $newFile->fileName = $originalName;
            $newFile->save();

            require_once CMS . '/vendor/blueimp/jquery-file-upload/server/php/UploadHandler.php';
            $uploader = new \UploadHandler(array(
                'upload_dir' => dirname($_SERVER['SCRIPT_FILENAME']) . '/../uploads/',
                'image_versions' => array()
            ), false);
            $_SERVER['HTTP_CONTENT_DISPOSITION'] = $newFile->id;
            $result = $uploader->post(false);

            $newFile->fileLocation = $newFile->id . '.' . $ext;
            $newFile->fileType = $result['files'][0]->type;
            $newFile->fileSize = $result['files'][0]->size;
            $newFile->save();

            if (file_exists(dirname($_SERVER['SCRIPT_FILENAME']) . '/../uploads/' . $result['files'][0]->name)) {
                rename(dirname($_SERVER['SCRIPT_FILENAME']) . '/../uploads/' . $result['files'][0]->name, dirname($_SERVER['SCRIPT_FILENAME']) . '/../uploads/' . $newFile->id . '.' . $ext);
            }

            return new Response(json_encode($newFile));
        }
        return new Response(json_encode(array(
            'failed'
        )));
    }

}