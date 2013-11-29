<?php
    namespace fitzlucassen\DALGenerator;

    class Config {
	private static $_DB;
	private static $_HOST;
	private static $_USER;
	private static $_PWD;
	private static $_PATH_ENTITIES;
	private static $_PATH_REPOSITORIES;
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
	public function getInstance(){
	    if(Config::$_cpt_instance === 0){
		Config::$_cpt_instance++;
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
	public function setDB($db){
	    Config::$_DB = $db;
	}
	
	/**
	 * SetHOST
	 * @param type $host
	 */
	public function setHOST($host){
	    Config::$_HOST = $host;
	}
	
	/**
	 * SetUSER
	 * @param type $user
	 */
	public function setUSER($user){
	    Config::$_USER = $user;
	}
	
	/**
	 * SetPWD
	 * @param type $pwd
	 */
	public function setPWD($pwd){
	    Config::$_PWD = $pwd;
	}
	
	/**
	 * SetPATHENTITIES
	 * @param type $path
	 */
	public function setPATHENTITIES($path){
	    Config::$_PATH_ENTITIES = $path;
	}
	
	/**
	 * SetPATHREPOSITORIES
	 * @param type $path
	 */
	public function setPATHREPOSITORIES($path){
	    Config::$_PATH_REPOSITORIES = $path;
	}
	
	
	
	/***********
	 * GETTERS *
	 ***********/
	/**
	 * GetDB
	 * @return type $db
	 */
	public function getDB(){
	    return Config::$_DB;
	}
	
	/**
	 * GetHOST
	 * @return type $host
	 */
	public function getHOST(){
	    return Config::$_HOST;
	}
	
	/**
	 * GetUSER
	 * @return type $user
	 */
	public function getUSER(){
	    return Config::$_USER;
	}
	
	/**
	 * GetPWD
	 * @return type $pwd
	 */
	public function getPWD(){
	    return Config::$_PWD;
	}
	
	/**
	 * GetPATHENTITIES
	 * @return type $path
	 */
	public function getPATHENTITIES(){
	    return Config::$_PATH_ENTITIES;
	}
	
	/**
	 * GetPATHREPOSITORIES
	 * @return type $path
	 */
	public function getPATHREPOSITORIES(){
	    return Config::$_PATH_REPOSITORIES;
	}
    }