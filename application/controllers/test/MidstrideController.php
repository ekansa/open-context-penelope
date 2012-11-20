<?php

class MidstrideController extends Zend_Controller_Action {

    function indexAction() {
            
    }
    
    public function ajaxcallAction()
    {
        //only handle ajax calls
        if ($this->_request->isXmlHttpRequest())
        {
            //don’t render the page - no view script is needed{
            $this->_helper->viewRenderer->setNoRender();
            //json is the intermediate language to pass messages between the server and client
            $jsonData = "";  //initialize to nothing
            //get the parameter as normal
            $testValue = $this->_request->getParam("test_value");
            //prepare a fancy highlight response back
            $jsonData =  "var highlight = dojo.animateProperty(
            {
                node: ’status_message’,duration: 500,
                properties: {
                    color:         { start: 'white', end: 'black' },
                    backgroundColor:{ start: '#fffecc', end: '#ffffff' }
                }
            });
            highlight.play();";
            //$this->_response->appendBody($jsonData);
        }
    }
    
} //end class
