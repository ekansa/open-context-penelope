<?php

class Importer_UserController extends Zend_Controller_Action
{
    public $counter = 0;
    function init()
    {  
        $this->view->baseUrl = $this->_request->getBaseUrl();
        Zend_Loader::loadClass('Zend_Debug');
    }
    
    //gets all users that are associated with a particular organization:
    /*function getUsersWithinOrgAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        
        Zend_Loader::loadClass('User'); //defined in User.php        
        $currentUser = User::getCurrentUser();
        
        $db = Zend_Registry::get('db');
        //1. get distinct list of child properties
        $select = $db->select()
            ->distinct()
            ->from  (
                        array('u' => 'users'),
                        array('id' => 'pk_user', 'user' => new Zend_Db_Expr("CONCAT(u.combined_name, ' - ', u.affiliation)"))
            )
            ->where ('u.affiliation = ?', $currentUser->affiliation);
        $stmt = $db->query($select);
        $rows = $stmt->fetchAll();
        
        $dataStore = array('label' => 'id', 'identifier' => 'id',
                'items' => $rows                        
            );
        echo Zend_Json::encode($dataStore);
    }*/
    
    //gets all users that are associated with a particular project:
    function getUsersAssociatedWithProjectAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $projectUUID    = $_REQUEST['projectUUID'];
        $db = Zend_Registry::get('db');

        // get all of the users who are associated with the project.
        $select = $db->select()
            ->distinct()
            ->from  (
                        array('u' => 'users'),
                        array('id' => 'uuid', 'user' => new Zend_Db_Expr("CONCAT(u.combined_name, ' - ', u.affiliation)"))
                        //array('id' => 'pk_user', 'user' => new Zend_Db_Expr("CONCAT(u.combined_name, ' - ', u.affiliation)"), 'personUUID' => 'uuid')
            )
            ->joinLeft(
                       array('link' => 'at_users_projects'),
                       'u.uuid = link.person_uuid'
            )
            ->where ('link.project_id = ?', $projectUUID);
        $stmt = $db->query($select);
        $rows = $stmt->fetchAll();
        
        $dataStore = array('label' => 'id', 'identifier' => 'id',
                'items' => $rows                        
            );
        echo Zend_Json::encode($dataStore);
    }
    
    function getRolesAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        
        $db = Zend_Registry::get('db');
        $select = $db->select()
            ->distinct()
            ->from  (
                        array('r' => 'w_lu_roles'),
                        array('id' => 'PK_ROLE', 'name' => 'ROLE_NAME')
            );
        $stmt = $db->query($select);
        $rows = $stmt->fetchAll();
        
        $dataStore = array('label' => 'id', 'identifier' => 'id',
                'items' => $rows                        
            );
        echo Zend_Json::encode($dataStore);
    }
    
    function getOrgsAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        
        $db = Zend_Registry::get('db');
        $select = $db->select()
            ->distinct()
            ->from  (
                        array('r' => 'users'),
                        array('id' => 'affiliation', 'name' => 'affiliation')
            );
        $stmt = $db->query($select);
        $rows = $stmt->fetchAll();
        
        $dataStore = array('label' => 'id', 'identifier' => 'id',
                'items' => $rows                        
            );
        echo Zend_Json::encode($dataStore);
    }
    
    function addResponsibilityAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        
        $projectUUID    = $_REQUEST['projectUUID'];
        $dataTableName  = $_REQUEST['dataTableName'];
        $personUUID     = $_REQUEST['personUUID'];
        $roleID         = $_REQUEST['roleID'];
        $linkAll        = ($_REQUEST['linkAll'] == "true") ? 1 : 0;
        
        $db = Zend_Registry::get('db');
        
        // 1) query for the role name:
        $select = $db->select()
            ->distinct()
            ->from  (
                        array('r' => 'w_lu_roles'),
                        array('id' => 'PK_ROLE', 'name' => 'ROLE_NAME')
            )
            ->where ('r.PK_ROLE = ?', $roleID);
        $row        = $db->query($select)->fetchObject();
        $roleName   = $row->name;
        
        // 2) query for the user in the users table:
        $select = $db->select()
            ->distinct()
            ->from  (
                array('u' => 'users')
            )
            ->where ('u.uuid = ?', $personUUID);
        $row            = $db->query($select)->fetchObject();
        $email          = $row->email;
        $affiliation    = $row->affiliation;

        
        // 3) check to see if there is already an association in place:
        $select = $db->select()
            ->distinct()
            ->from  (
                array('p' => 'persons_st_des')
            )
            ->where ('p.source_id = ?', $dataTableName)
            ->where ('p.uuid = ?', $personUUID);
        $stmt = $db->query($select);
        $rows = $stmt->fetchAll();
        
        //if association already exists:
        if(sizeof($rows) == 0)
        {
            // a) associate user's email with this data table:
            $data = array(
                'project_id'   => $projectUUID,
                'source_id'          => $dataTableName,
                'uuid'       => $personUUID,
                'stnd_var'          => 'email',
                'stnd_vals'         => $email,
                'link_all'          => $linkAll);            
            $db->insert('persons_st_des', $data);
            
            // b) associate user's affiliation with this data table:
            $data = array(
                'project_id'   => $projectUUID,
                'source_id'          => $dataTableName,
                'uuid'       => $personUUID,
                'stnd_var'          => 'affil',
                'stnd_vals'         => $affiliation,
                'link_all'          => $linkAll);
            $db->insert('persons_st_des', $data);
            
            // c) associate user's role with this data table:
            $data = array(
                'project_id'   => $projectUUID,
                'source_id'          => $dataTableName,
                'uuid'       => $personUUID,
                'stnd_var'          => 'role',
                'stnd_vals'         => $roleName,
                'link_all'          => $linkAll);
            $db->insert('persons_st_des', $data);
            
            echo "User association successfully added.";
            
        }
        //otherwise:
        else
        {
            // a) update database:            
            Zend_Loader::loadClass('Table_UserStandardDescription');
            $userStandardDescription = new Table_UserStandardDescription();
            
            //update email:
            $where = "uuid = '" . $personUUID . "' and source_id = '" .$dataTableName . "' and stnd_var = 'email'";
            $data = array(
                'stnd_vals'     => $email,
                'link_all'      => $linkAll
            );
            $userStandardDescription->update($data, $where);
            
            // b) update affiliation:
            $where = "uuid = '" . $personUUID . "' and source_id = '" .$dataTableName . "' and stnd_var = 'affil'";
            $data = array(
                'stnd_vals'     => $affiliation,
                'link_all'      => $linkAll
            );
            $userStandardDescription->update($data, $where);
            
            // c) update role:
            $where = "uuid = '" . $personUUID . "' and source_id = '" .$dataTableName . "' and stnd_var = 'role'";
            $data = array(
                'stnd_vals'     => $roleName,
                'link_all'      => $linkAll
            );
            $userStandardDescription->update($data, $where);
            
            echo "User association successfully updated. " . $personUUID;
        }
    }
    
    function getDataOwnerAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $dataTableName  = $_REQUEST['dataTableName'];
        
        $db = Zend_Registry::get('db');
        
        $select = $db->select()
            ->distinct()
            ->from(
                    array('at' => 'persons_st_des'),
                    array('id' => 'at.uuid', 'at.link_all')
            )
            ->joinLeft(
                       array('u' => 'users'),
                       'at.uuid = u.uuid',
                       array('fullName' => 'combined_name')
            )
            ->joinLeft(
                array('roles' => 'persons_st_des'),
                'roles.uuid = u.uuid',
                array('role' => 'stnd_vals')
            )
            ->where ("at.source_id = '" . $dataTableName . "'")
            ->where ("at.stnd_var = 'email'")
            ->where ("roles.stnd_var = 'role'") 
            ->order(array('fullName'));
        
        $stmt = $db->query($select);
        $rows = $stmt->fetchAll();
        
        if(sizeof($rows) == 0)
        {
            echo "";
            return;
        }
        
        //create a layout:
        $layout = array();       
        $layout[0] = array(
            'field'     =>  'fullName',
            'name'      =>  'Name',
            'width'     =>  '115px',
            'editable'  =>  false
        );
        $layout[1] = array(
            'field'     =>  'role',
            'name'      =>  'Role',
            'width'     =>  '90px',
            'editable'  =>  false
        );
        $layout[2] = array(
            'field'     =>  'link_all',
            'name'      =>  'Linked',
            'width'     =>  '90px',
            'editable'  =>  false,            
            'formatter' => 'isLinked'
        );
        $layout[3] = array(
            'name'      =>  '&nbsp;',
            'width'     =>  '50px',
            'editable'  =>  false,            
            'formatter' => 'deleteResponsibiliy'
        );
        
        //create datastore helper:        
        Zend_Loader::loadClass('Layout_DataGridHelper');
        $dgHelper = new Layout_DataGridHelper();
        $dgHelper->setDataRecords($rows, "id");
        $dgHelper->layout = $layout;
        
        echo Zend_Json::encode($dgHelper);
            
    }
    
    function deleteResponsibilityAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $db = Zend_Registry::get('db');
        
        $dataTableName  = $_REQUEST['dataTableName'];
        $personUUID     = $_REQUEST['personUUID'];
        
        $db->delete('persons_st_des', "source_id = '" . $dataTableName . "' and uuid = '" . $personUUID . "'");
        
        echo "User responsibility was successfully removed.";
    }
    
    function addNewUserAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('User');
        require_once 'App/Util/GenericFunctions.php';
        
        $firstName      = $_REQUEST['firstName'];
        $lastName       = $_REQUEST['lastName'];
        $fullName       = $_REQUEST['fullName'];
        $middleInit     = $_REQUEST['middleInit'];
        $initials       = $_REQUEST['initials'];
        $affiliation    = $_REQUEST['affiliation'];
        $email          = $_REQUEST['email'];
        $projectUUID    = $_REQUEST['projectUUID'];
        $userUUID       = GenericFunctions::generateUUID();
        $message        = "";
        $db = Zend_Registry::get('db');
        
        //first, check to see if the user's already in there:
        $select = $db->select()
            ->distinct()
            ->from  (
                array('u' => 'users')
            )
            ->where ('u.email = ?', $email);
        $stmt = $db->query($select);
        $rows = $stmt->fetchAll();
        
        //if user exists:
        if(sizeof($rows) == 0)
        {
            //insert to the database:
            $data = array(
                      'first_name'              => $firstName,
                      'last_name'               => $lastName,
                      'combined_name'               => $fullName,
                      'mid_init'             => $middleInit,
                      'initials'                => $initials,
                      'affiliation'             => $affiliation,
                      'email'                   => $email,
                      'uuid'             => $userUUID,
                      'fk_user_last_modified'   => User::getCurrentUser()->id
                );
            $db->insert('users', $data);
            
            $message = "User successfully added!";
        }
        else
        {
            foreach($rows as $row)
                $userUUID = $row['uuid'];
            $message = $email . " already exists in the database.  This existing record has now been associated with the current project.";   
        }
        
        //check to see if the user is already associated with the project:
        $select = $db->select()
            ->from (
                array('at' => 'at_users_projects')
            )
            ->where ('at.person_uuid = ?', $userUUID)
            ->where ('at.project_id = ?', $projectUUID);
            
        $stmt = $db->query($select);
        $rows = $stmt->fetchAll();
        
        //if association not already present, create it:
        if(sizeof($rows) == 0)
        {
            $data = array('person_uuid'  => $userUUID, 'project_id' => $projectUUID);
            $db->insert('at_users_projects', $data);
        }
        echo $message;
    }
}