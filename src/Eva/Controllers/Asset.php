<?php
namespace Pz\Controllers;

use Imagick;
use Pz\Common\Utils;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class Asset extends AssetView
{
    public function connect(Application $app)
    {
        $controllers = parent::connect($app);
        $controllers->match('/', array($this, 'assets'))->bind('assets');
        $controllers->match('/upload/', array($this, 'upload'))->bind('upload-assets');
        $controllers->match('/json/{id}/', array($this, 'json'))->bind('assets-json');
        $controllers->match('/{id}/', array($this, 'assets'))->bind('assets-folder');

        return $controllers;
    }

    private function _ancestors($all, $currentId, &$result)
    {
        foreach ($all as $itm) {
            if ($currentId == $itm->id) {
                array_unshift($result, $itm);
                $this->_ancestors($all, $itm->parentId, $result);
            }
        }
    }

    public function assets(Application $app, Request $request, $id = 0)
    {
        $assetClass = $app['assetClass'];
        $newFolder = new $assetClass($app['em']);
        $newFolder->isFolder = 1;
        $newFolder->parentId = $id;
        $formBuilder = $app['form.factory']->createBuilder(new \Pz\Forms\Folder(), $newFolder);
        $form = $formBuilder->getForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $newFolder->save();
            return $app->redirect($app->url('assets-folder', array('id' => $id)));
        }

        $ancestors = array();
        $this->_ancestors($assetClass::data($app['em'], array(
            'whereSql' => 'entity.isFolder = 1',
        )), $id, $ancestors);

        $json = $this->json($app, $request, $id);
        $json = json_decode($json->getContent());
        foreach ($json[0] as &$itm) {
            $itm->_childNum = count($assetClass::data($app['em'], array(
                'whereSql' => 'entity.parentId = :v1',
                'params' => array(
                    'v1' => $itm->id
                ),
            )));
        }
        return $app['twig']->render('assets.twig', array(
            'form' => $form->createView(),
            'currentId' => $id,
            'folders' => $json[0],
            'files' => $json[1],
            'ancestors' => $ancestors,
            'returnURL' => Utils::getURL(),
        ));

    }

    public function json(Application $app, Request $request, $id)
    {
        $assetClass = $app['assetClass'];
        if ($id == -1) {
            if ($app['session']->get('last-folder')) {
                $id = $app['session']->get('last-folder');
            } else {
                $id = 0;
            }
        } else {
            $app['session']->set('last-folder', $id);
        }

        $folders = $assetClass::data($app['em'], array(
            'whereSql' => 'entity.parentId = :v1 AND entity.isFolder = 1',
            'params' => array(
                'v1' => $id
            ),
        ));

        $files = $assetClass::data($app['em'], array(
            'whereSql' => 'entity.parentId = :v1 AND entity.isFolder != 1',
            'params' => array(
                'v1' => $id
            ),
        ));

        $ancestors = array();
        $this->_ancestors($assetClass::data($app['em'], array(
            'whereSql' => 'entity.isFolder = 1',
        )), $id, $ancestors);

        return $app->json(array($folders, $files, $ancestors, $id));
    }


    public function upload(Application $app, Request $request)
    {
        $assetClass = $app['assetClass'];
        $files = $request->files->get('files');
        if ($files && is_array($files) && count($files) > 0) {
            $originalName = $files[0]->getClientOriginalName();
            $ext = pathinfo($originalName, PATHINFO_EXTENSION);

            $newFile = new $assetClass($app['em']);
            $newFile->isFolder = 0;
            $newFile->parentId = $request->request->get('parentId');
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