<?php
ini_set("memory_limit", "1024M");
ini_set("max_execution_time", "0");
class UploaddataController extends Zend_Controller_Action
{
    function init()
    {
        $this->view->baseUrl = $this->_request->getBaseUrl();
        Zend_Loader::loadClass('User');
        Zend_Loader::loadClass('Form_Upload');
        Zend_Loader::loadClass('Zend_Debug');
    }

    function indexAction()
    {
        $projectID                  = $_REQUEST['projectID'];
        $projectUUID                = $_REQUEST['projectUUID'];
        $form                       = new Form_Upload($projectID, $projectUUID);
        $this->view->form           = $form;

    }
    
    function submitAction()
    {
        require_once 'App/Util/Excel/TableImporter.php';

        $fileName       = $_FILES['excelfile']['tmp_name'];
        $ReadFilename = $_FILES["excelfile"]["name"];
        $projectID      = $_REQUEST['projectID'];
        $projectUUID    = $_REQUEST['projectUUID'];
        $description    = $_REQUEST['description'];

        /*
        Zend_Debug::dump($fileName);
        Zend_Debug::dump($projectID);
        Zend_Debug::dump($projectUUID);
        Zend_Debug::dump($description);
        Zend_Debug::dump($_REQUEST);
        return;
	*/
        
        $db = Zend_Registry::get('db');
        $sql = "SET collation_connection = utf8_unicode_ci;";
	$db->query($sql, 2);
	$sql = "SET NAMES utf8;";
	$db->query($sql, 2);
        
        //helper object parses Excel file into a simple data structure:
        $tableImporter = new TableImporter($fileName, $projectID, $projectUUID, $description, $ReadFilename);
        //load data from Excel file into memory:
        $tableImporter->loadData();
        //commit the data from memory to the database:
        $dataTableName = $tableImporter->commitDataToDB();
        
        //associate the current user with the project:
        Zend_Loader::loadClass('User'); //defined in User.php        
        $currentUser = User::getCurrentUser();
        $db = Zend_Registry::get('db');
        $select = $db->select()
            ->from (
                array('at' => 'at_users_projects')
            )
            ->where ('at.person_uuid = ?', $currentUser->uuid)
            ->where ('at.project_id = ?', $projectUUID);
            
        $stmt = $db->query($select);
        $rows = $stmt->fetchAll();
        
        //if association not already present, create it:
        if(sizeof($rows) == 0)
        {
            $data = array('person_uuid'  => $currentUser->uuid, 'project_id' => $projectUUID);
            $db->insert('at_users_projects', $data);
        }
        
        echo "Data Successfully Uploaded." . "<br />";
        echo "Number of Columns: " . $tableImporter->_numColumns . "<br />";
        echo "Number of Rows: " . $tableImporter->_numRows . "<br />";
        echo "Table Name: " . $dataTableName;
        
        $this->view->dataTableName = $dataTableName;
        
        //echo $table->_numColumns.'<br />';
        //echo $table->_numRows.'<br />';
        //echo implode(" text,", $table->_headerRow);
        //Zend_Debug::dump($table->_headerRow);
        //Zend_Debug::dump($table->_headerAliasRow);
        //Zend_Debug::dump($table->_dataRecords);        
        
    }
    
}