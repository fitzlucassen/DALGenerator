<?php
    require_once 'library/Config.php';
    require_once 'library/Sql.php';
    require_once 'library/FileManager.php';
    require_once 'library/Utilities.php';

    $Config = fitzlucassen\DALGenerator\Config::getInstance();
    $fm = fitzlucassen\DALGenerator\FileManager::getInstance();
    
    if(PHP_SAPI == "cli"){
	$Config->setDB($argv[1]);		// database
	$Config->setHOST($argv[2]);		// database host
	$Config->setUSER($argv[3]);		// user name
	$Config->setPWD($argv[4]);		// password
	$Config->setPATHENTITIES($argv[5]);	// The path where entities will be created
	$Config->setPATHREPOSITORIES($argv[6]);	// The path where repositories will be created
    }
    else {
	/*************************
	 * PUT YOUR CONFIGS HERE *
	 *************************/
       $Config->setDB("passangerv2");					    // database
       $Config->setHOST("localhost");					    // database host
       $Config->setUSER("root");					    // user name
       $Config->setPWD("");						    // password
       $Config->setPATHENTITIES("C:/wamp/www/DALGenerator/Entity/");	    // The path where entities will be created
       $Config->setPATHREPOSITORIES("C:/wamp/www/DALGenerator/Repository/");// The path where repositories will be created
       
       // If there is some links into your tables, you have to precise these right here.
       // 
       // Example: you will have "getSongs" method into the "album" class, and you'll have "getALbum" instead of "getAlbumId" into "song" class
       $Config->setLink(array(	'album' => array('song' => 'OneToMany'), 
				'song' => array('album' => 'OneToOne'),
				'routeurl' => array('rewrittingurl' => 'OneToMany'),
				'rewrittingurl' => array('routeurl' => 'OneToOne')));
       /*******
	* END *
	*******/
    }
    $Connexion = new fitzlucassen\DALGenerator\Sql($Config->getDB(), $Config->getHOST(), $Config->getUSER(), $Config->getPWD());
    
    // The last argument is the array of all attributs you want to add into your classes
    $Utilities = new fitzlucassen\DALGenerator\Utilities($Connexion, 2, array("_pdoHelper"));
    // The argument is an array of which table you want to ignore
    $master_array = $Utilities->getTablesArray(array('header','lang'));

    $Utilities->createClasses($Config->getPATHENTITIES(), $Config->getPATHREPOSITORIES(), $Config->getLink());
    
    if(defined('STDIN')){
	exit(0);
    }