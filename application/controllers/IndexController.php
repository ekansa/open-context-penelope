<?php
require_once 'App/Controller/PenelopeController.php';   //handles the functionality common across controllers

class IndexController extends App_Controller_PenelopeController
{
    function indexAction()
    {
        //call to process query parameters:
        parent::indexAction();
        $this->_redirect('/project');
    }

}