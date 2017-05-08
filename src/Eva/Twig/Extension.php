<?php
namespace Eva\Twig;

use Eva\Route\URL;
use Eva\Tools\Utils;
use PrettyDateTime\PrettyDateTime;
use \Twig_Extension;

class Extension extends Twig_Extension
{

    public function getName()
    {
        return 'eva.extension';
    }

    public function getFilters()
    {
        return array(
            'json_decode' => new \Twig_Filter_Method($this, 'jsonDecode'),
            'ceil' => new \Twig_Filter_Method($this, 'ceil'),
            'purify' => new \Twig_Filter_Method($this, 'purify'),
            'nestable' => new \Twig_Filter_Method($this, 'nestable'),
            'nav' => new \Twig_Filter_Method($this, 'nav'),
            'prettydatetime' => new \Twig_Filter_Method($this, 'prettydatetime'),
        );
    }

    public function jsonDecode($str)
    {
        return json_decode($str);
    }

    public function ceil($number)
    {
        return ceil($number);
    }

    public function purify($str)
    {
        $purify = new \HTMLPurifier();
        return $purify->purify($str);
    }

    public function nestable($node)
    {
        if (count($node->_c) == 0) {
            return '';
        }
        $str = '<ol class="dd-list">';
        foreach ($node->_c as $itm) {
            $str .= '<li class="dd-item dd3-item content-container" data-id="' . $itm->id . '">';
            $str .= '<div class="dd-handle dd3-handle"></div>';
            $str .= '<div class="dd-handle dd3-handle"></div>';
            $str .= '<div class="dd3-content">';
            $str .= '<span>' . $itm->title . '</span>';
            $str .= '<a href="#" data-content="' . $itm->id . '" data-model="' . $itm->__modelClass . '" data-status="' . $itm->__active . '" class="js-status isactive btn btn-xs btn-circle ' . ($itm->__active == 1 ? 'btn-info' : 'btn-danger') . ' btn-outline"><i class="fa ' . ($itm->__active == 1 ? 'fa-check' : 'fa-ban') . '"></i></a>';
            $str .= '<a href="/pz/content/edit/4/' . URL::encodeURL(URL::getUrl()) . '/' . $itm->id . '/" class="edit btn btn-xs btn-circle btn-primary"><i class="fa fa-pencil"></i></a>';
            $str .= '<a href="/pz/content/copy/4/' . URL::encodeURL(URL::getUrl()) . '/' . $itm->id . '/" class="copy btn btn-xs btn-circle btn-default"><i class="fa fa-copy"></i></a>';
            $str .= '<a href="#" data-content="' . $itm->id . '" data-model="' . $itm->__modelClass . '" class="js-delete delete btn btn-xs btn-circle btn-danger"><i class="fa fa-times"></i></a>';
            $str .= '</div>';
            $str .= $this->nestable($itm);
            $str .= '</li>';
        }
        $str .= '</ol>';
        return $str;
    }

    public function nav($node, $options = array())
    {
        $str = '';
        $str = "<ul" . ((isset($options['class']) && $options['class']) ? " class=\"{$options['class']}\"" : '') . ">";
        foreach ($node->_c as $itm) {
            if ($itm->__active != 1) {
                continue;
            }
            $str .= "<li" . ((isset($options['selected']) && $options['selected']->id == $itm->id) ? " class=\"active\"" : '') . ">";
            $str .= "<a href=\"" . ($itm->url ?: urlencode($itm->title)) . "\" >{$itm->title}</a>";
            $str .= $this->nav($itm);
            $str .= "</li>";
        }
        $str .= "</ul>";

        return $str;
    }

    public function prettydatetime($value)
    {
        return PrettyDateTime::parse($value);
    }
}