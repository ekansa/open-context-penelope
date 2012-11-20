<?php  class RpcController extends Zend_Controller_Action
{
    function indexAction()
    {
        $this->view->title = "Step 1";
        
        //add "User" object to the view:
        //$user = new User('vanwars');
        //$this->view->user = $user;        
    }
    
    public function smdAction()
    {
        $class = $this->_getParam('class');
        $server = new Zend_Json_Server();
        $server->getServiceMap()->setDojoCompatible(true);
        $server->getServiceMap()->setTransport('POST')
            ->setTarget($this->getHelper('url')->url(array('controller'=>'rpc', 'action'=>'service')))
            ->setId($this->getHelper('url')->url(array('controller'=>'rpc', 'action'=>'service')));
        $server->setClass($class);
        $this->view->data = $server->getServiceMap();
        $this->render('service');
    }
    
    public function serviceAction()
    {
        $class = $this->_getParam('class');
        $server = new Zend_Json_Server();
        $server->setClass($class);
        $server->setAutoEmitResponse(true);
        $server->handle();
    }
} 