<?php

namespace Eva\Controllers;

use Eva\Route\Nav;
use Eva\Route\Node;
use PrettyDateTime\PrettyDateTime;
use Silex\Application;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class File extends FileView
{
    public function connect(Application $app)
    {
        $controllers = parent::connect($app);
        $controllers->match('/', array($this, 'route'));
        $controllers->match('/folders/', array($this, 'folders'));
        $controllers->match('/files/', array($this, 'files'));
        $controllers->match('/upload/', array($this, 'upload'));
        $controllers->match('/move-file/', array($this, 'moveFile'));
        $controllers->match('/add-folder/', array($this, 'addFolder'));
        $controllers->match('/edit-folder/', array($this, 'editFolder'));
        $controllers->match('/update-folders/', array($this, 'updateFolders'));
        $controllers->match('/delete-folder/{id}/', array($this, 'deleteFolder'));
        $controllers->match('/delete-file/{id}/', array($this, 'deleteFile'));
        return $controllers;
    }

    public function folders(Application $app, Request $request)
    {
        $folderOpenMaxLimit = 10;
        $currentFolderId = $request->get('currentFolderId');

        $childrenCount = array();
        $nodes = array();
        $data = \Eva\ORMs\Asset::data($app['zdb'], array('whereSql' => 'm.isFolder = 1'));
        foreach ($data as $itm) {
            if (!isset($childrenCount[$itm->__parentId])) {
                $childrenCount[$itm->__parentId] = 0;
            }
            $childrenCount[$itm->__parentId]++;

            $node = new \stdClass();
            $node->id = $itm->id;
            $node->parentId = $itm->__parentId;
            $node->rank = $itm->__rank;
            $node->text = $itm->title;
            $node->state = array( 'opened' => true, 'selected' => $currentFolderId == $itm->id );
            $nodes[] = $node;
        }

        foreach ($nodes as &$itm) {
            if (isset($childrenCount[$itm->id]) && $childrenCount[$itm->id] >= $folderOpenMaxLimit && $itm->id != $currentFolderId) {
                $itm->state['opened'] = false;
            }
        }

        $root = new \stdClass();
        $root->id = -1;
        $root->parentId = -2;
        $root->text = 'Home';
        $root->state = array( 'opened' => true, 'selected' => $currentFolderId == -1 );
        $root->rank = 0;
        $nav = new Nav($nodes);
        return $app->json($nav->root($root));
    }

    public function files(Application $app, Request $request)
    {
        $baseurl = '/pz/files/?currentFolderId=';
        $currentFolderId = $request->get('currentFolderId') ?: -1;
        $keyword = $request->get('keyword');

        $nodes = array();
        $data = \Eva\ORMs\Asset::data($app['zdb'], array('whereSql' => 'm.isFolder = 1'));
        foreach ($data as $itm) {
            $nodes[] = new Node($itm->id, $itm->__parentId, $itm->__rank, 1, $itm->title, null, $baseurl . $itm->id);
        }
        $nav = new Nav($nodes);
        $root = new Node(-1, -2, 0, 1, 'Home', $baseurl . '-1');
        $root = $nav->root($root);
        $path = $root->path($currentFolderId);

        if ($keyword) {
            $data = \Eva\ORMs\Asset::data($app['zdb'], array(
                'whereSql' => 'm.isFolder = 0 AND m.title LIKE ?',
                'params' => array("%$keyword%"),
            ));
        } else {
            $data = \Eva\ORMs\Asset::data($app['zdb'], array(
                'whereSql' => 'm.isFolder = 0 AND m.__parentId = ?',
                'params' => array($currentFolderId),
            ));
        }

        foreach ($data as &$itm) {
            $itm->__added = PrettyDateTime::parse(new \DateTime($itm->__added));
        }
        return $app->json(array(
            'currentFolder' => end($path),
            'keyword' => $keyword,
            'path' => $path,
            'files' => $data,
        ));
    }

    public function upload(Application $app, Request $request)
    {
        $files = $request->files->get('files');
        if ($files && is_array($files) && count($files) > 0) {
            $originalName = $files[0]->getClientOriginalName();
            $ext = pathinfo($originalName, PATHINFO_EXTENSION);

            $rank = \Eva\ORMs\Asset::data($app['zdb'], array(
                'select' => 'MIN(m.__rank) AS min',
                'orm' => 0,
                'whereSql' => 'm.__parentId = ?',
                'params' => array($request->get('__parentId')),
                'oneOrNull' => 1,
            ));
            $min = $rank['min'] - 1;

            $orm = new \Eva\ORMs\Asset($app['zdb']);
            $orm->isFolder = 0;
            $orm->__parentId = $request->get('__parentId');
            $orm->__rank = $min;
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

            $orm->__added = PrettyDateTime::parse(new \DateTime($orm->__added));
            return new Response(json_encode($orm));
        }
        return new Response(json_encode(array(
            'failed'
        )));
    }

    public function moveFile(Application $app, Request $request)
    {
        $orm = \Eva\ORMs\Asset::getById($app['zdb'], $request->get('id'));
        if (!$orm) {
            $app->abort(404);
        }

        $orm->__parentId = $request->get('__parentId');
        $orm->save();
        return new Response('OK');
    }

    public function addFolder(Application $app, Request $request)
    {
        $rank = \Eva\ORMs\Asset::data($app['zdb'], array(
            'select' => 'MAX(m.__rank) AS max',
            'orm' => 0,
            'whereSql' => 'm.__parentId = ?',
            'params' => array($request->get('__parentId')),
            'oneOrNull' => 1,
        ));
        $max = $rank['max'] + 1;

        $orm = new \Eva\ORMs\Asset($app['zdb']);
        $fields = array_keys($orm->getFieldMap());
        foreach ($fields as $itm) {
            if ($request->get($itm)) {
                $orm->$itm = $request->get($itm);
            }
        }
        $orm->__rank = $max;
        $orm->isFolder = 1;
        $orm->save();
        return new Response('OK');
    }

    public function editFolder(Application $app, Request $request)
    {
        $orm = \Eva\ORMs\Asset::getById($app['zdb'], $request->get('id'));
        if (!$orm) {
            $app->abort(404);
        }

        $fields = array_keys($orm->getFieldMap());
        foreach ($fields as $itm) {
            if ($request->get($itm)) {
                $orm->$itm = $request->get($itm);
            }
        }
        $orm->save();
        return new Response('OK');
    }

    public function updateFolders(Application $app, Request $request)
    {
        $data = json_decode($request->get('data'));
        foreach ($data as $itm) {
            $orm = \Eva\ORMs\Asset::getById($app['zdb'], $itm->id);
            $orm->__parentId = $itm->__parentId;
            $orm->__rank = $itm->__rank;
            $orm->save();
        }
        return new Response('OK');
    }

    public function deleteFolder(Application $app, Request $request, $id)
    {
        $orm = \Eva\ORMs\Asset::getById($app['zdb'], $id);
        if (!$orm) {
            $app->abort(404);
        }

        $children = \Eva\ORMs\Asset::getORMsByField($app['zdb'], '__parentId', $id);
        foreach ($children as $itm) {
            $this->deleteFolder($app, $request, $itm->id);
        }
        if (!$orm->isFolder) {
            if (file_exists(dirname($_SERVER['SCRIPT_FILENAME']) . '/../uploads/' . $orm->fileLocation)) {
                unlink(dirname($_SERVER['SCRIPT_FILENAME']) . '/../uploads/' . $orm->fileLocation);
            }
        }
        $orm->delete();
        return new Response('OK');
    }

    public function deleteFile(Application $app, Request $request, $id)
    {
        $orm = \Eva\ORMs\Asset::getById($app['zdb'], $id);
        if (!$orm) {
            $app->abort(404);
        }
        if (file_exists(dirname($_SERVER['SCRIPT_FILENAME']) . '/../uploads/' . $orm->fileLocation)) {
            unlink(dirname($_SERVER['SCRIPT_FILENAME']) . '/../uploads/' . $orm->fileLocation);
        }
        $orm->delete();
        return new Response('OK');
    }
}