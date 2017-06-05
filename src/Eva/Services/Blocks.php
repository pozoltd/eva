<?php
namespace Eva\Services;

use Eva\Route\Node;
use Eva\Route\Tree;
use Eva\Route\Nav;
use Eva\Tools\Utils;
use Silex\ServiceProviderInterface;
use Silex\Application;

class Blocks implements ServiceProviderInterface
{
    private $nodes = array();

    public function register(Application $app)
    {
        $app['blocks'] = $this;
    }

    public function boot(Application $app)
    {
        $this->app = $app;
    }

    public function data()
    {
        $blocks = \Eva\ORMs\ContentBlock::active($this->app['zdb']);
        foreach ($blocks as $block) {
            $items = json_decode($block->items);
            foreach ($items as &$item) {
                $choices = array();
                if ($item->widget == 9 || $item->widget == 10) {
                    $conn = $this->app['zdb']->getConnection();
                    $stmt = $conn->prepare($item->sql);
                    $stmt->execute();
                    foreach ($stmt->fetchAll() as $key => $val) {
                        $choices[$val['key']] = $val['value'];
                    }
                }
                $item->choices = $choices;
            }
            $block->items = $items;
        }
        return $blocks;
    }

    public function value($value)
    {
        $blocks = array();
        $result = \Eva\ORMs\ContentBlock::active($this->app['zdb']);
        foreach ($result as $itm) {
            $blocks[$itm->id] = $itm;
        }

        $value = $value ?: '[]';
        $sections = json_decode($value);
        foreach ($sections as $section) {
            $contentBlocks = $section->blocks;
            foreach ($contentBlocks as $contentBlock) {
                if (isset($blocks[$contentBlock->block])) {
                    $block = $blocks[$contentBlock->block];
                    $items = json_decode($block->items);
                    foreach ($items as &$item) {
                        $choices = array();
                        if ($item->widget == 9 || $item->widget == 10) {
                            $conn = $this->app['zdb']->getConnection();
                            $stmt = $conn->prepare($item->sql);
                            $stmt->execute();
                            foreach ($stmt->fetchAll() as $key => $val) {
                                $choices[$val['key']] = $val['value'];
                            }
                        }
                        $item->choices = $choices;
                    }
                    $contentBlock->items = $items;
                }
            }
        }
//        while (@ob_end_clean());
//        Utils::dump($sections);exit;
        return $sections;
    }
}
