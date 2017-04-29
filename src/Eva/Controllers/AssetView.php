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

class AssetView implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $controllers->match('/image/{imageAsset}/', array($this, 'image'))->bind('image-original');
        $controllers->match('/image/{imageAsset}/{imageSize}/', array($this, 'image'))->bind('image');
        $controllers->match('/download/{imageAsset}/', array($this, 'download'))->bind('download-assets');
        return $controllers;
    }

    public function image(Application $app, Request $request, $imageAsset, $imageSize = null)
    {
        $assetClass = $app['assetClass'];
        $asset = $assetClass::findById($app['em'], $imageAsset);
        if (!$asset) {
            $asset = $assetClass::findByField($app['em'], 'title', $imageAsset);
            if (!$asset) {
                $app->abort(404);
            }
        }
        $fileType = $asset->fileType;
        $fileName = $asset->fileName;
        $file = dirname($_SERVER['SCRIPT_FILENAME']) . '/../uploads/' . $asset->fileLocation;
        if ($imageSize) {
            if ((file_exists($file) && getimagesize($file)) || ('application/pdf' == $fileType)) {
                $imageSizeClass = $app['imageSizeClass'];
                $size = $imageSizeClass::findById($app['em'], $imageSize);
                if (!$size) {
                    $size = $imageSizeClass::findByField($app['em'], 'title', $imageSize);
                    if (!$size) {
                        $app->abort(404);
                    }
                }

                $cache = dirname($_SERVER['SCRIPT_FILENAME']) . '/../cache/image/';
                if (!file_exists($cache)) {
                    mkdir($cache, 0777, true);
                }
                $thumbnail = $cache . md5($asset->id . '-' . $size->id . '-' . $size->width) . (('application/pdf' == $fileType) ? '.jpg' : '.' . pathinfo($asset->fileName, PATHINFO_EXTENSION));
                if (!file_exists($thumbnail)) {
                    if ('application/pdf' == $fileType) {
                        $image = new imagick($file . '[0]');
                        $image->setImageFormat('jpg');
//                        $image->setColorspace(imagick::COLORSPACE_RGB);
                        $image->setImageBackgroundColor('white');
                        $image->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
                        $image->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
                        $image->thumbnailImage($size->width, null);
                        $image->writeImage($thumbnail);
                    } else {
                        $image = new Imagick($file);
                        $image->adaptiveResizeImage($size->width, 0);
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
            $file = __DIR__ . '/images/noimage.jpg';
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

    public function download(Application $app, $imageAsset)
    {
        $assetClass = $app['assetClass'];
        $asset = $assetClass::findById($app['em'], $imageAsset);
        if (!$asset) {
            $asset = $assetClass::findByField($app['em'], 'title', $imageAsset);
            if (!$asset) {
                $app->abort(404);
            }
        }
        $fileType = $asset->fileType;
        $fileName = $asset->fileName;
        $file = dirname($_SERVER['SCRIPT_FILENAME']) . '/../uploads/' . $asset->fileLocation;
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