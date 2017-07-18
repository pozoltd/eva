<?php

namespace Eva\Cms;

use Silex\Application;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Imagick;


class FileView extends RouterCms
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $controllers->match('/files/image/{id}', array($this, 'preview'));
        $controllers->match('/files/image/{id}/{size}', array($this, 'preview'));
        $controllers->match('/files/download/{id}', array($this, 'download'));
        return $controllers;
    }

    public function preview(Application $app, Request $request, $id, $size = null)
    {
        $orm = \Eva\ORMs\Asset::getById($app['zdb'], $id);
        if (!$orm) {
            $orm = \Eva\ORMs\Asset::getORMsByField($app['zdb'], 'track', $id);
            if (!$orm) {
                $app->abort(404);
            }
        }
        $fileType = $orm->fileType;
        $fileName = $orm->fileName;
        $file = dirname($_SERVER['SCRIPT_FILENAME']) . '/../uploads/' . $orm->fileLocation;
        if ($size) {
            if ((file_exists($file) && getimagesize($file)) || ('application/pdf' == $fileType)) {
                $sizeOrm = \Eva\ORMs\AssetSize::getById($app['zdb'], $size);
                if (!$sizeOrm) {
                    $sizeOrm = \Eva\ORMs\AssetSize::getORMByField($app['zdb'], 'title', $size);
                    if (!$sizeOrm) {
                        $app->abort(404);
                    }
                }

                $cache = dirname($_SERVER['SCRIPT_FILENAME']) . '/../cache/image/';
                if (!file_exists($cache)) {
                    mkdir($cache, 0777, true);
                }
                $thumbnail = $cache . md5($orm->id . '-' . $sizeOrm->id . '-' . $sizeOrm->width) . (('application/pdf' == $fileType) ? '.jpg' : '.' . pathinfo($orm->fileName, PATHINFO_EXTENSION));
                if (!file_exists($thumbnail)) {
                    if ('application/pdf' == $fileType) {
                        $image = new Imagick($file . '[0]');
                        $image->setImageFormat('jpg');
//                        $image->setColorspace(imagick::COLORSPACE_RGB);
                        $image->setImageBackgroundColor('white');
                        $image->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
                        $image->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
                        $image->thumbnailImage($sizeOrm->width, null);
                        $image->writeImage($thumbnail);
                    } else {
                        $image = new Imagick($file);
                        $image->adaptiveResizeImage($sizeOrm->width, 0);
                        $image->writeImage($thumbnail);
                    }
                }
                $file = $thumbnail;
            }
        } else {
            $stream = function () use ($file) {
                readfile($file);
            };
            return $app->stream($stream, 200, array(
                'Content-Type' => $fileType,
                'Content-length' => filesize($file),
                'Content-Disposition' => 'filename="' . $fileName . '"'
            ));
        }

        if (!file_exists($file) || !getimagesize($file)) {
            $file = __DIR__ . '/../Files/images/noimage.jpg';
        }
        $stream = function () use ($file) {
            readfile($file);
        };
        return $app->stream($stream, 200, array(
            'Content-Type' => 'image/jpg',
            'Content-length' => filesize($file),
            'Content-Disposition' => 'filename="' . $fileName . '"'
        ));
    }

    public function download(Application $app, $id)
    {
        $orm = \Eva\ORMs\Asset::getById($app['zdb'], $id);
        if (!$orm) {
            $orm = \Eva\ORMs\Asset::getORMByField($app['zdb'], 'title', $id);
            if (!$orm) {
                $app->abort(404);
            }
        }
        $fileType = $orm->fileType;
        $fileName = $orm->fileName;
        $file = dirname($_SERVER['SCRIPT_FILENAME']) . '/../uploads/' . $orm->fileLocation;
        if (!file_exists($file)) {
            $app->abort(404);
        }
        $stream = function () use ($file) {
            readfile($file);
        };
        return $app->stream($stream, 200, array(
            'Content-Type' => $fileType,
            'Content-length' => filesize($file),
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
        ));
    }
}