<?php
    require_once 'library/Config.php';
    require_once 'library/Sql.php';
    require_once 'library/FileManager.php';
    require_once 'library/Utilities.php';

    $Config = fitzlucassen\DALGenerator\Config::getInstance();
    $fm = fitzlucassen\DALGenerator\FileManager::getInstance();
    
    /*************************
     * PUT YOUR CONFIGS HERE *
     *************************/
    $Config->setDB("passangerv2");					    // database
    $Config->setHOST("localhost");					    // database host
    $Config->setUSER("root");						    // user name
    $Config->setPWD("");						    // password
    $Config->setPATHENTITIES("C:/wamp/www/DALGenerator/Entity/");	    // The path where entities will be created
    $Config->setPATHREPOSITORIES("C:/wamp/www/DALGenerator/Repository/");   // The path where repositories will be created
    /*******
     * END *
     *******/
    
    $Connexion = new fitzlucassen\DALGenerator\Sql($Config->getDB(), $Config->getHOST(), $Config->getUSER(), $Config->getPWD());
    $Utilities = new fitzlucassen\DALGenerator\Utilities($Connexion, 2);
    $master_array = $Utilities->getTablesArray();

    $Utilities->createClasses($Config->getPATHENTITIES(), $Config->getPATHREPOSITORIES());