<?php
class User
{ 
    public $id;
    public $uuid;
    public $userName;
    public $firstName;
    public $lastName;
    public $fullName;
    public $middleInit;
    public $initials;
    public $affiliation;
    public $email;
    public $projects;
    public $orgName;
    
    //converts
    function User($_data) 
    {
        if(is_array($_data))
        {
            $this->userName     = $_data['username'];
            $this->firstName    = $_data['first_name'];
            $this->lastName     = $_data['last_name'];
            $this->fullName     = $_data['combined_name'];
            $this->middleInit   = $_data['mid_init'];
            $this->initials     = $_data['initials'];
            $this->affiliation  = $_data['affiliation'];
            $this->email        = $_data['email'];
            $this->id           = $_data['pk_user'];
            $this->uuid         = $_data['uuid'];
        }
        else
        {
            $this->userName     = $_data->username;
            $this->firstName    = $_data->first_name;
            $this->lastName     = $_data->last_name;
            $this->fullName     = $_data->combined_name;
            $this->middleInit   = $_data->mid_init;
            $this->initials     = $_data->initials;
            $this->affiliation  = $_data->affiliation;
            $this->email        = $_data->email;
            $this->id           = $_data->pk_user;
            $this->uuid         = $_data->uuid;    
        }
        //$this->projects     = $this->getProjects();     
    }
    
    public static function getCurrentUser()
    {       
        //get this information from the session:
        $auth = Zend_Auth::getInstance();
        $_data = $auth->getIdentity();
        return new User($_data);
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    public function getUuid()
    {
        return $this->uuid;
    }
    
    public function getFullName()
    {
        return $this->firstName . ' ' .  $this->lastName;
    }
    
    public function getUserName()
    {
        return $this->userName;
    }
    
    public function getAffiliation()
    {
        return $this->affiliation;
    }
    
    public function getEmail()
    {
        return $this->email;
    }
    
    public function getProjects()
    {
        $this->projects = new ArrayObject();
        Zend_Loader::loadClass('Project');
        Zend_Loader::loadClass('Table_Project');
        $projectTable = new Table_Project();
        $rows = $projectTable->fetchAll();
        foreach ($rows as $row) 
        { 
            $project = new Project($row);
            //workaround for some encoding issues:
            $project->shortDesc = urlencode($project->shortDesc);
            $project->longDesc = urlencode($project->longDesc);
            //$project->longDesc = "(text)";
            $this->projects->append($project);
        } 
        //while ($row = $projectTable->fetch())
        //{
        //    $this->projects->append(new Project($row));
        //}
        return $this->projects;    
    }
}