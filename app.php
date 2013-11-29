<?php

    require_once 'config.class.php';
    require_once 'sql.class.php';
    require_once 'utilities.class.php';

    $Config = Config::GetInstance();
    
    /*************************
     * PUT YOUR CONFIGS HERE *
     *************************/
    $Config->SetDB("passangerv2");					// database
    $Config->SetHOST("localhost");					// database host
    $Config->SetUSER("root");						// user name
    $Config->SetPWD("");						// password
    $Config->SetPATHENTITIES("C:/wamp/www/DALGenerator/Entity/");	// The path where entities will be created
    $Config->SetPATHREPOSITORIES("C:/wamp/www/DALGenerator/Repository/");	// The path where repositories will be created
    /*******
     * END *
     *******/
    
    $Connexion = new Sql(DB, HOST, USER, PWD);
    $Utilities = new Utilities($Connexion);
    $master_array = $Utilities->GetTablesArray();

    $Utilities->CreateClasses();