<?php
    class Config {
	private $_DB;
	private $_HOST;
	private $_USER;
	private $_PWD;
	private $_PATH_ENTITIES;
	private $_PATH_REPOSITORIES;
	private static $_cpt_instance = 0;
	
	/**
	 * Constructor called only one time
	 */
	private function __construct() {
	}
	
	/**
	 * GetInstance -> return an instance of Config class only if there isn't any one else
	 * @return \Config|boolean
	 */
	public function GetInstance(){
	    if($this->_cpt_instance === 0){
		$this->_cpt_instance++;
		return new Config();
	    }
	    else
		return false;
	}
	
	/***********
	 * SETTERS *
	 ***********/
	/**
	 * SetDB
	 * @param type $db
	 */
	public function SetDB($db){
	    $this->_DB = $DB;
	}
	
	/**
	 * SetHOST
	 * @param type $host
	 */
	public function SetHOST($host){
	    $this->_HOST = $host;
	}
	
	/**
	 * SetUSER
	 * @param type $user
	 */
	public function SetUSER($user){
	    $this->_USER = $user;
	}
	
	/**
	 * SetPWD
	 * @param type $pwd
	 */
	public function SetPWD($pwd){
	    $this->_PWD = $pwd;
	}
	
	/**
	 * SetPATHENTITIES
	 * @param type $path
	 */
	public function SetPATHENTITIES($path){
	    $this->_PATH_ENTITIES = $path;
	}
	
	/**
	 * SetPATHREPOSITORIES
	 * @param type $path
	 */
	public function SetPATHREPOSITORIES($path){
	    $this->_PATH_REPOSITORIES = $path;
	}
    }