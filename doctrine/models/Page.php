<?php

/**
 * Page
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
class Page extends BasePage
{
    private $_document;

    public function postInsert($event)
    {
        $this->code_36 = User_Utils_Digits::convertTo36($this->id);
        $this->save();
    }
    
    public function saveToFile()
    {
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $filename = PUBLIC_PATH . $bootstrap->getOption('path_to_files') . '/' . $this->code_36 . '.html';
        file_put_contents($filename, $this->Content->contents);
    }

    public function populateFromResponse(Zend_Http_Response $response)
    {
        if ($content_type = $response->getHeader('Content-Type')) {
            preg_match( '@([\w/+]+)(;\s+charset=(\S+))?@i', $content_type, $matches );
            if ( isset( $matches[1] ) )
                $mime = $matches[1];
            if ( isset( $matches[3] ) )
                $charset = $matches[3];
        }
        
        $content = $response->getBody();
        
        if(!isset($charset)){
            preg_match( '@<meta\s+http-equiv="Content-Type"\s+content="([\w/]+)(;\s+charset=([^\s"]+))?@i',
                $content, $matches );
            if ( isset( $matches[1] ) && !isset($mime) )
                $mime = $matches[1];
            if ( isset( $matches[3] ) )
                $charset = $matches[3];
        }
        
        if (!isset($charset)) {
            $charset = mb_detect_encoding($content, "ASCII,UTF-8,ISO-8859-1,windows-1251, windows-1252,iso-8859-15");
            if ($charset === false) {
                $charset = 'utf-8';
            }
        }

        $tidy_config = array(
            'input-encoding'    =>  $charset,
            'output-encoding'   =>  'utf8',
            'drop-proprietary-attributes' => true,
            'hide-comments' => true,
            'logical-emphasis' => true,
            'numeric-entities' => true,
            'output-xhtml' => true,
            'wrap' => 0
        );

        $tidy = new tidy;
        $tidy->parseString($content, $tidy_config);
        $tidy->cleanRepair();
        $content = $tidy->value;

        $isUtf8 = in_array($charset, array('utf-8', 'utf8', 'UTF-8', 'UTF8', 'Utf8', 'Utf-8'));
        
        if (!$isUtf8) {
            $content = mb_convert_encoding($content, 'utf-8', $charset);
        }
        
        $content = mb_convert_encoding($content, 'html-entities', 'utf-8');
        
        if (preg_match('@<meta\s+http-equiv="Content-Type"[^>]*>@is', $content)) {
            $content = preg_replace(
                '@<meta\s+http-equiv="Content-Type"[^>]*>@is',
                '',
                $content
            );
        }

        $content = preg_replace(
            '@<head\b([^>]*)>@is',
            '<head$1><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />',
            $content
        );
        
        $document = new DOMDocument();
        @$document->loadHTML($content);

        $head = $document->getElementsByTagName('head')->item(0);
        $xpath = new DOMXpath($document);

        $keywordsTag = $xpath->query('meta[@name="keywords"]', $head);
        if ($keywordsTag->length > 0) {
            $this->keywords = $keywordsTag->item(0)->getAttribute('content');
        }

        $descriptionTag = $xpath->query('meta[@name="description"]', $head);
        if ($descriptionTag->length > 0) {
            $this->description = $descriptionTag->item(0)->getAttribute('content');
        }

        if($xpath->query('base', $head)->length == 0) {
            $uri = FinalView_Uri_Http::fromString($this->url);
            $head->insertBefore(self::_buildElement(array(
                'tag'       =>  'base',
                'attribs'   =>  array(
                    'href'    =>  $uri->getBaseUri(),
                )
            ), $document), $head->firstChild);
        }

        $titleTag = $document->getElementsByTagName('title')->item(0);
        $title = $titleTag->textContent;
        if (empty($title)) {
            $title = $this->url;
        }

        $this->title = $title;

        $content = $document->saveHTML();

        $this->Content->contents = $content;
        return $this;
    }
    
    private static function _buildElement($elem, $document)
    {
        if (!isset($elem['tag'])) {
            trigger_error('_buildElement must get array with tag key');
        }

        $elemObj = $document->createElement($elem['tag']);
        foreach ((array)@$elem['attribs'] as $key   =>  $value) {
            $attrib = $document->createAttribute($key);
            $attribValue = $document->createTextNode($value);
            $attrib->appendChild($attribValue);
            $elemObj->appendChild($attrib);
        }

        if (is_array(@$elem['inner_content'])) {
            foreach ($elem['inner_content'] as $innerElement) {
                $elemObj->appendChild(self::_buildElement($innerElement, $document) );
            }
        }elseif (is_string(@$elem['inner_content']) ) {
            $innerContent = $document->createTextNode($elem['inner_content']);
            $elemObj->appendChild($innerContent);
        }

        return $elemObj;
    }
    
    public function modifyContent(array $modifiers)
    {
        $filter = new Zend_Filter_Word_UnderscoreToCamelCase();
        $content = $this->Content->contents;

        foreach ($modifiers as $key=>$value) {
            if (is_numeric($key)) {
                $modifier = $value;
                $params = array();
            }else{
                $modifier = $key;
                $params = $value;
            }
            
            $method = '_' . lcfirst($filter->filter($modifier)) . 'CModifier';

            if (!method_exists($this, $method)) {
                throw new FinalView_Doctrine_Table_Exception('there is no modifier ' . $modifier);
            }

            $content = $this->$method($content, $params);
        }
        
        $this->Content->contents = $content;
    }
    
    public function modifyDOM(array $modifiers)
    {
        $filter = new Zend_Filter_Word_UnderscoreToCamelCase();
        $dom = new DOMDocument();
        $content = mb_convert_encoding($this->Content->contents, 'html-entities', 'utf-8');
        @$dom->loadHTML($content);

        foreach ($modifiers as $key=>$value) {
            if (is_numeric($key)) {
                $modifier = $value;
                $params = array();
            }else{
                $modifier = $key;
                $params = $value;
            }
            
            $method = '_' . lcfirst($filter->filter($modifier)) . 'DModifier';
            
            if (!method_exists($this, $method)) {
                throw new FinalView_Doctrine_Table_Exception('there is no modifier ' . $modifier);
            }
            
            $this->$method($dom, $params);
        }

        $content = $dom->saveHTML();
        $this->Content->contents = $content;
    }
    
    private function _coverBodyInDivCModifier($content, array $params)
    {
        $htmlBefore = '<div';
        
        foreach ($params as $key=>$value) {
            switch ($key) {
                case 'id':
                    $htmlBefore .= ' id="' . $value . '"';
                break;
                case 'style':
                    $htmlBefore .= ' style="' . $this->_rsa($value) . '"';
                break;
            }
        }
        
        $htmlBefore .= '>';
        
        $content = preg_replace("/<body\b([^>]*)>/i", '<body$1>' . $htmlBefore, $content);
        $content = preg_replace("/<\/body>/i", '</div></body>', $content);
        
        return $content;
    }
    
    private function _injectScriptsDModifier($document, $scripts)
    {
        $body = $document->getElementsByTagName('body')->item(0);

        foreach ($scripts as $script) {
            if (is_string($script)) {
                $script_data = array(
                    'tag'       =>  'script',
                    'attribs'   =>  array(
                        'src'   =>  BASE_PATH . 'scripts/' . $script,
                        'type'  =>  'text/javascript'
                    )
                );
            }

            $body->appendChild(self::_buildElement($script_data, $document));
        }
    }
    
    private function _injectCssDModifier($document, $scripts)
    {
        $body = $document->getElementsByTagName('body')->item(0);

        foreach ($scripts as $script) {
            if (is_string($script)) {
                $script_data = array(
                    'tag'       =>  'link',
                    'attribs'   =>  array(
                        'href'      =>  BASE_PATH . 'css/' . $script,
                        'rel'       =>  'stylesheet',
                        'type'      =>  'text/css'
                    )
                );
            }

            $body->appendChild(self::_buildElement($script_data, $document));
        }
    }
    
    private function _injectIframesDModifier($document, $frames)
    {
        $body = $document->getElementsByTagName('body')->item(0);

        foreach ($frames as $frame) {
            if (isset($frame['style']) && is_array($frame['style'])) {
                $frame['style'] = $this->_rsa($frame['style']);
            }
            $body->appendChild($this->_buildElement(array(
                'tag'       =>  'iframe',
                'attribs'   =>  $frame
            ), $document));
        }
    }
    
    /*
        Render style attribute
    */
    private function _rsa(array $style)
    {
        $s = '';
        foreach ($style as $key=>$value) {
            $s .= $key . ':' . $value . ';';
        }
        return $s;
    }
    
    private function _injectOverlapStructureDModifier($document, $ids)
    {
        $cWidth = '100%'; $cHeight = '1200px';

        $parent_div_style = $this->_rsa(array(
            'position'          =>  'relative',
            'z-index'           =>  '10',
            'width'             =>  $cWidth,
        ));
        
        $divs_style = $this->_rsa(array(
            'position'          =>  'absolute',
            'z-index'           =>  '10',
            'background-color'  =>  'white',
            'opacity'           =>  '0.8',
            'filter'            =>  'alpha(Opacity=80)',
            'left'              =>  '0px',
            'top'               =>  '0px',
        ));
        
        $transparent_div_style = $this->_rsa(array(
            'position'          =>  'absolute',
            'z-index'           =>  '5',
            'background-color'  =>  'white',
            'opacity'           =>  '0',
            'filter'            =>  'alpha(Opacity=0)',
            'left'              =>  '0px',
            'top'               =>  '0px',
            'width'             =>  $cWidth,
            'height'            =>  $cHeight,
        ));

        $body = $document->getElementsByTagName('body')->item(0);
        $body->insertBefore(self::_buildElement(array(
            'tag'       =>  'div',
            'attribs'   =>  array(
                'style' =>  $parent_div_style
            ),
            'inner_content' =>  array(
                array(
                    'tag'       =>  'div',
                    'attribs'   =>  array(
                        'id'    =>  $ids['transd'],
                        'style' =>  $transparent_div_style
                    )
                ),
                array(
                    'tag'       =>  'div',
                    'attribs'   =>  array(
                        'id'    =>  $ids['ld'],
                        'style' =>  $divs_style . ';width:0px;height:' . $cHeight . ';'
                    )
                ),
                array(
                    'tag'       =>  'div',
                    'attribs'   =>  array(
                        'id'    =>  $ids['td'],
                        'style' =>  $divs_style . ';width:0px;height:0px;'
                    )
                ),
                array(
                    'tag'       =>  'div',
                    'attribs'   =>  array(
                        'id'    =>  $ids['bd'],
                        'style' =>  $divs_style . ';width:0px;height:0px;'
                    )
                ),
                array(
                    'tag'       =>  'div',
                    'attribs'   =>  array(
                        'id'    =>  $ids['rd'],
                        'style' =>  $divs_style . ';width:' . $cWidth . ';height:' . $cHeight . ';'
                    )
                ),
            )
        ), $document), $body->firstChild);
    }
    
    private function _injectFeedbackFormDModifier($document, $params)
    {
        $div_style = $this->_rsa(array(
            'position'          =>  'absolute',
            'z-index'           =>  '20',
            'display'           =>  'none',
        ));

        $body = $document->getElementsByTagName('body')->item(0);
        $body->appendChild(self::_buildElement(array(
            'tag'       =>  'div',
            'attribs'   =>  array(
                'id'    =>  $params['fd'],
                'style' =>  $div_style
            ),
            'inner_content' =>  array(
                array(
                    'tag'       =>  'form',
                    'attribs'   =>  array(
                        'action'    =>  $params['form_action'],
                        'method'    =>  'post'
                    ),
                    'inner_content' =>  array(
                        array(
                            'tag'   =>  'input',
                            'attribs'   =>  array(
                                'type'  =>  'hidden',
                                'id'    =>  $params['lwi'],
                                'name'  =>  $params['lwi'] . '_left_width'
                            )
                        ),
                        array(
                            'tag'   =>  'input',
                            'attribs'   =>  array(
                                'type'  =>  'hidden',
                                'id'    =>  $params['thi'],
                                'name'  =>  $params['thi'] . '_top_height'
                            )
                        ),
                        array(
                            'tag'   =>  'input',
                            'attribs'   =>  array(
                                'type'  =>  'hidden',
                                'id'    =>  $params['twi'],
                                'name'  =>  $params['twi'] . '_top_width'
                            )
                        ),
                        array(
                            'tag'   =>  'input',
                            'attribs'   =>  array(
                                'type'  =>  'hidden',
                                'id'    =>  $params['bti'],
                                'name'  =>  $params['bti'] . '_bottom_top'
                            )
                        ),
                        array(
                            'tag'   =>  'input',
                            'attribs'   =>  array(
                                'type'  =>  'hidden',
                                'id'    =>  $params['documentW'],
                                'name'  =>  $params['documentW'] . '_document_width'
                            )
                        ),
                        array(
                            'tag'   =>  'input',
                            'attribs'   =>  array(
                                'type'  =>  'hidden',
                                'id'    =>  $params['documentH'],
                                'name'  =>  $params['documentH'] . '_document_height'
                            )
                        ),
                        array(
                            'tag'   =>  'input',
                            'attribs'   =>  array(
                                'type'  =>  'button',
                                'value' =>  'Build Page'
                            )
                        ),
                    )
                )
            )
        ), $document));
    }
    
    private function _overlapEmbedObjectsDModifier($document, $params)
    {
        $xpath = new DOMXpath($document);

        $objects = $document->getElementsByTagName('object');

        foreach ($objects as $object) {
            $wmodeParam = $xpath->query('param[@name="wmode"]', $object);
            if ($wmodeParam->length < 1) {
             $object->appendChild(self::_buildElement(array(
                 'tag'       =>  'param',
                 'attribs'   =>  array(
                     'name'   =>  'wmode',
                     'value'  =>  'opaque'
                 )
             ), $document));
            }else{
                $wmodeParam->item(0)->setAttribute('value', 'opaque');
            }
        }

        $embeds = $document->getElementsByTagName('embed');
        foreach ($embeds as $embed) {
            $embed->setAttribute('wmode', 'opaque');
        }
    }
    
    private function _assignJsVariablesDModifier($document, $params)
    {
        $body = $document->getElementsByTagName('body')->item(0);
        $body->appendChild(self::_buildElement(array(
            'tag'       =>  'script',
            'attribs'   =>  array(
                'type'  =>  'text/javascript'
            ),
            'inner_content' =>  '$(function(){$(document).data("params", ' . Zend_Json::encode($params) . ')} )'
        ), $document));
    }
}