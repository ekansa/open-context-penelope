<?php

class AjaxsampleController extends Zend_Controller_Action
{
    function init()
    {
        $this->initView();
        $this->view->baseUrl = $this->_request->getBaseUrl();
        Zend_Loader::loadClass('Zend_Debug');
        Zend_Loader::loadClass('Zend_HTTP_Client');
        Zend_Loader::loadClass('User'); //defined in User.php 
    }

    function indexAction()
    {
        //echo "<p>in IndexController::indexAction()</p>";
        $this->view->title = "Zend Ajax 101";
    }

    function getDataAction()
    {
        $this->_helper->viewRenderer->setNoRender();
    
        $state = $_REQUEST['state'];
        
        if ($state == 'do')
        {
            echo '<h1>exciting text retrieved from server!</h1>';
        }
        else if ($state == 'undo')
        {
            echo 'reset to boring...';
        }
        else
        {
            echo 'unknown state parameter passed to server!!';
        }
    }
    
    function processUserAction()
    {
        //decode decodes json into an object
        //encode encodes an object into json
        $this->_helper->viewRenderer->setNoRender();
        $encodedValue = $_REQUEST['jObj'];//$this->getRequest()->getParam('jObj');
        $rawResponse = Zend_Json::decode($encodedValue, Zend_Json::TYPE_OBJECT);
        //var_dump($rawResponse);
        //echo $rawResponse->userName;
        
        $user = new User();        
        echo Zend_Json::encode($user);
        
        //echo $encodedValue;
        
        
        //$username = $_REQUEST['state'];
        //$password = $_REQUEST['password'];
        //$json = new services_JSON();
        //if ( $action == "getArticle") {
        //  $jsonArray = $json->decode($data);
          //$article = getArticle($jsonArray);
        //  print $json->encode($article);
        //}  

        
        //$requestObject = Zend_Json::encode($encodedValue, Zend_Json::TYPE_OBJECT);
        //echo $encodedValue . ' - ' . $requestObject;        
        //$user = new User('vanwars');
        //$this->view->user = $user;
        //echo $encodedValue; //$requestObject;
        
        //$user = new User('vanwars');
        //$responseObject = Zend_Json::encode($user, Zend_Json::TYPE_OBJECT);
        //echo "HI"; //$responseObject;
        //var_dump($username . " " . $password);
        //$request = $filterGet->getRaw('request');
        //$httpClient = self::getHttpClient();
        //$encodedValue = $httpClient->request(Zend_Http_Client::GET);

        
        
        
        //$type = $requestObject->type;
        //$query = $requestObject->query;

        
        
        //$phpNative = Zend_Json::decode($encodedValue, Zend_Json::TYPE_OBJECT);
        //$phpNative = Zend_Json::decode($encodedValue);
        //Zend_Registry::getInstance()->get('userID');
        //$filterGet = Zend::registry('fGet');
        //$userID = $filterGet->getRaw('userID');

        //add "User" object to the view:
        //$user = new User('vanwars');
        //$this->view->user = $user;
        
        //echo $user->getFullName();
        //echo $requestObject; //$phpNative->userID;
        //echo 'blah blah';
        
    }
}

    
    

