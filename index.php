<?php
error_reporting(E_ALL|E_STRICT);
date_default_timezone_set('Europe/London');
set_include_path('.' . PATH_SEPARATOR . './library'
. PATH_SEPARATOR . './application/models/'
. PATH_SEPARATOR . './application/controllers/'

. PATH_SEPARATOR . get_include_path());
include "Zend/Loader.php";

Zend_Loader::loadClass('Zend_Controller_Front');
Zend_Loader::loadClass('Zend_Config_Ini');
Zend_Loader::loadClass('Zend_Registry');
Zend_Loader::loadClass('Zend_Db');
Zend_Loader::loadClass('Zend_Db_Table');
Zend_Loader::loadClass('Zend_View');
Zend_Loader::loadClass('Zend_Controller_Action_Helper_ViewRenderer');
Zend_Loader::loadClass('Zend_Json');
Zend_Loader::loadClass('Zend_Http_Client');
Zend_Loader::loadClass('Zend_Auth');
Zend_Loader::loadClass('Zend_Dojo_Form');
Zend_Loader::loadClass('Zend_Dojo');
Zend_Loader::loadClass('Zend_Debug');
Zend_Loader::loadClass('App_Constants');
Zend_Loader::loadClass('App_Xml_Generic');
Zend_Loader::loadClass('Zend_Controller_Router_Route_Regex');
Zend_Loader::loadClass('Zend_Controller_Router_Route_Module');
Zend_Loader::loadClass('Zend_Controller_Request_Http');

//$registry = new Zend_Registry(array('index' => $value));
//Zend_Registry::setInstance($registry);


// load configuration
$config = new Zend_Config_Ini('./application/config.ini', 'general');
$registry = Zend_Registry::getInstance();
$registry->set('config', $config);

// setup database
$db = Zend_Db::factory($config->db->adapter,
$config->db->config->toArray());
Zend_Db_Table::setDefaultAdapter($db); 
Zend_Registry::set('db', $db);

        
//$authUsers = new ArrayObject();
//$authUsers->append
Zend_Registry::set('authUsers', new ArrayObject()); 

// setup controller
$frontController = Zend_Controller_Front::getInstance();
$frontController->throwExceptions(true);

//specify all of the directories and subdirectories in which controllers are found:
$frontController->setControllerDirectory('./application/controllers');
$frontController->addControllerDirectory('./application/controllers/importer', 'importer');

//generic way to parse query parameters:
$router = $frontController->getRouter();
$compat = new Zend_Controller_Router_Route_Module(array());
$router->addRoute('default', $compat);



// creates a view object, and then instructs the front controller to send this object to the action controller
$view = new Zend_View();
$frontController->setParam('view', $view);  

/*
$view->addHelperPath('../application/views/helpers', 'My_View_Helper');
$view->addHelperPath('My/Helper/', 'My_Helper');
$viewRenderer = new Zend_Controller_Action_Helper_ViewRenderer(); 
$viewRenderer->setView($view);
*/
//$view->addHelperPath('My/Helper/', 'My_Helper');

// run!
//echo 'dispatching...';
$frontController->dispatch();