<?php

class Admin_Bootstrap extends FinalView_Application_Module_Bootstrap 
{

	/**
    * load helpers
    *
    */
    public function init()
    {
        $layout = Zend_Layout::getMvcInstance();
        $layout->setLayoutPath(APPLICATION_PATH . '/layouts/');
        
        $layout->setLayout('admin');  
    }
    
    protected function _initAutoload() 
    {        
        $autoloader = new Zend_Application_Module_Autoloader(array(
            'namespace' => 'Admin',
            'basePath'  => APPLICATION_PATH . '/modules/admin',
        ));
        $autoloader->addResourceTypes(array(
            'grid' => array
                (
                    'path' => 'grid', 
                    'namespace' => 'Grid', 
                ), 
        ));
        
        return $autoloader;
    }    
}