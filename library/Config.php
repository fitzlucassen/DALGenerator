<?php
    namespace fitzlucassen\DALGenerator\library;

    class Config {
	private static $_DB;
	private static $_HOST;
	private static $_USER;
	private static $_PWD;
	private static $_PATH;
	private static $_link = array();
	
	/**
	 * Constructor called only one time
	 */
	public function __construct() {
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
	public function setPATH($path){
	    Config::$_PATH = $path;
	}
	
	/**
	 * setLink
	 * @param type $array
	 */
	public function setLink($array){
	    Config::$_link = $array;
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
	public function getPATH(){
	    return Config::$_PATH;
	}
	
	/**
	 * getLink
	 * @return type $array
	 */
	public function getLink(){
	    return Config::$_link;
	}
    }