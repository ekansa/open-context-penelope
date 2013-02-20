<?php

class App_Controller_PenelopeController extends Zend_Controller_Action
{
    public $project;
    public $controllerName;
    public $user;
    
    function init()
    {
        parent::init();
            
        $this->view->baseUrl = $this->_request->getBaseUrl();
        Zend_Loader::loadClass('User'); //defined in User.php        
        Zend_Loader::loadClass('Project');
        Zend_Loader::loadClass('Form_Login');
        Zend_Loader::loadClass('Table_Project');
        Zend_Loader::loadClass('Zend_Debug');
        Zend_Loader::loadClass('Zend_Dojo_Data');
        Zend_Loader::loadClass('Form_Upload');
        Zend_Loader::loadClass('Zend_Layout');
        Zend_Loader::loadClass('Layout_Navigation');
        
        //1) do privileges check:
        $auth = Zend_Auth::getInstance();
        if (!$auth->hasIdentity())
        {
            $this->_redirect('auth/login');
            return;
        }          
    }
    
    function indexAction()
    {
        $this->initializeGlobals();
        
        //if no controller is defined, redirect:
        if (!isset($this->controllerName))
        {
            $this->_redirect('/project');
            return;
        }
        
        /*
        var_dump($this->controllerName);
        echo 'requires a project: ';
        var_dump(array_search($this->controllerName, Layout_Navigation::getPagesRequiringProject())) . '<br />';
        echo 'requires a datatable: ';
        var_dump(array_search($this->controllerName, Layout_Navigation::getPagesRequiringDataTable())) . '<br />';
        echo 'isProjectDefined: ' . $this->isProjectDefined() . '<br />';
        echo 'isDataTableDefined: ' . $this->isDataTableDefined() . '<br />';
        */
        //if no project is defined and it should be, redirect:
        if(
           !$this->isProjectDefined() &&
           array_search($this->controllerName, Layout_Navigation::getPagesRequiringProject()) !== false
        )
        {
            $this->_redirect('/project');
            return;
        }
         
           
        //if no data table is defined and it should be, redirect:
        if(!$this->isDataTableDefined())
        {
            //echo 'TABLE IS NOT DEFINED<br />';
            if(array_search($this->controllerName, Layout_Navigation::getPagesRequiringDataTable()) !== false)
            {
                $this->_redirect('/project');
                return;
            }
        }
    }
    
    private function isProjectDefined()
    {
        return isset($this->project);
    }
    
    private function isDataTableDefined()
    {
        //return (boolean)false;
        if(isset($this->project))
            return $this->project->dataTableName != null && strlen($this->project->dataTableName) > 0;
        return false;
    }
    
    //redirect to login page if not logged in:
    /*function preDispatch()
    {
        $auth = Zend_Auth::getInstance();
        if (!$auth->hasIdentity())
            $this->_redirect('auth/login');
    }*/

    function initializeGlobals()
    {
        //1) get parameters and initialize objects from querystring:
        $projectUUID            = $this->_request->getParam('projectUUID');
        $dataTableName          = $this->_request->getParam('dataTableName');
        $this->controllerName   = $this->_request->getParam('controller');
        
        //Zend_Debug::dump($this->_request->getParams());
        
        //2) set selected project and datastore:
        //echo 'calling the controller...<br />';
        if(isset($projectUUID) && strlen($projectUUID) >= 1)
        {
            //echo $projectUUID . '<br />';
            //$projectUUID = 'D3E2EEB4-7561-42BF-0AA8-33EBCA6A6729'; //'5339364E-E954-42AE-0245-DFF5B3EFDC88';
            //echo $projectUUID . '<br />';
            
            
            $this->project = Project::getProjectByUUID($projectUUID);
            if(isset($dataTableName))
                $this->project->setFileSummaryInformation($dataTableName);
        }        
        if(isset($this->project))
            $this->view->project = $this->project;
        
        //3)  set user:
        $this->user = User::getCurrentUser();
        $this->view->user = $this->user;
        
        //4) initialize navigation:
        $this->view->navigation = new Layout_Navigation($this->controllerName, $this->project);
        
        //5) initialize layouts:
        $layout = new Zend_Layout();
        
        $layout->setLayout('state');
        $this->view->state = $layout->render();

        $layout->setLayout('sidebar');
        $this->view->sidebar = $layout->render();
        
        $layout->setLayout('header');
        $this->view->header = $layout->render();        
        
        $layout->setLayout('footer');
        $this->view->footer = $layout->render();
    }
}