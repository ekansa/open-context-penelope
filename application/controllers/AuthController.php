<?php

class AuthController extends Zend_Controller_Action 
{
    function init()
    {
        $this->initView();
        $this->view->baseUrl = $this->_request->getBaseUrl();
    }
        
    function indexAction()
    {
        $this->_redirect('/project');
    }
    
    function loginAction()
    {
        $this->view->message = '';
        if ($this->_request->isPost()) {
            // collect the data from the user
            Zend_Loader::loadClass('Zend_Filter_StripTags');
            $filter = new Zend_Filter_StripTags();
            $username = $filter->filter($this->_request->getPost('username'));
            $password = $filter->filter($this->_request->getPost('password'));
            
            if (empty($username)) {
                $this->view->message = 'Please provide a username.';
            } else {
                // setup Zend_Auth adapter for a database table
                Zend_Loader::loadClass('Zend_Auth_Adapter_DbTable');
                $db = Zend_Registry::get('db');
                $authAdapter = new Zend_Auth_Adapter_DbTable($db);
                $authAdapter->setTableName('users');
                $authAdapter->setIdentityColumn('username');
                $authAdapter->setCredentialColumn('password');
                
                // Set the input credential values to authenticate against
                $authAdapter->setIdentity($username);
                $authAdapter->setCredential($password);
                
                // do the authentication 
                $auth = Zend_Auth::getInstance();
                $result = $auth->authenticate($authAdapter);
                if ($result->isValid()) {
                    // success : store database row to auth's storage system
                    // (not the password though!)
                    $data = $authAdapter->getResultRowObject(null, 'password');
                    $auth->getStorage()->write($data);
                    
                    //Array to track authorized users:
                    //$authUsers = Zend_Registry::get('authUsers');
                    //$authUsers->append($username);
                    
                    $this->_redirect('/project');
                } else {
                    // failure: clear database row from session
                    $this->view->message = 'Login failed.';
                }
            }
        }
        
        $this->view->title = "Log in";
        
        //5) initialize layouts:
        Zend_Loader::loadClass('Zend_Layout');
        $layout = new Zend_Layout();
        
        $layout->setLayout('header');
        $this->view->header = $layout->render();        
        
        $layout->setLayout('footer');
        $this->view->footer = $layout->render();
        
        
        $this->render();
        
    }
    
    function logoutAction()
    {
        Zend_Auth::getInstance()->clearIdentity();
        $this->_redirect('/');
    }
}