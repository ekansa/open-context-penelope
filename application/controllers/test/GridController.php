<?php

class GridController extends Zend_Controller_Action 
{
    function init()
    {
        $this->initView();
        $this->view->baseUrl = $this->_request->getBaseUrl();
    }
        
    function indexAction()
    {
        //$this->_redirect('/import');
    }
        

}