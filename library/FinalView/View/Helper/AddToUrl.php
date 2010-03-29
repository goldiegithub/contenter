<?php

/**
* Pagination
* 
*/
class FinalView_View_Helper_AddToUrl extends Zend_View_Helper_Abstract
{
    
    public function addToUrl(array $params = array()) 
    {
        $request_uri_parts = parse_url(Zend_Controller_Front::getInstance()
            ->getRequest()->getRequestUri());
        $path = array_shift($request_uri_parts);
        $query = !empty($request_uri_parts) 
            ? array_shift($request_uri_parts) 
            : '';
        
        parse_str($query, $query_array);
        $query = http_build_query(array_merge(
            $query_array, $params));
        
        
        return $path . '?' . $query; 
    }    
}