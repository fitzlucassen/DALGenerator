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
    // If there is some links into your tables, you have to precise these right here.
    // Example: you will have "getSongs" method into the "album" class, and you'll have "getALbum" instead of "getAlbumId" into "song" class
    $Config->setLink(array('album' => array('song' => 'OneToMany'), 'song' => array('album' => 'OneToOne')));
    /*******
     * END *
     *******/
    
    $Connexion = new fitzlucassen\DALGenerator\Sql($Config->getDB(), $Config->getHOST(), $Config->getUSER(), $Config->getPWD());
    $Utilities = new fitzlucassen\DALGenerator\Utilities($Connexion, 2, array("_pdoHelper"));
    $master_array = $Utilities->getTablesArray();

    $Utilities->createClasses($Config->getPATHENTITIES(), $Config->getPATHREPOSITORIES(), $Config->getLink());