<?php
    require_once 'library/config.class.php';
    require_once 'library/sql.class.php';
    require_once 'library/fileManager.class.php';
    require_once 'library/utilities.class.php';

    $Config = fitzlucassen\DALGenerator\Config::GetInstance();
    $fm = fitzlucassen\DALGenerator\FileManager::GetInstance();
    
    /*************************
     * PUT YOUR CONFIGS HERE *
     *************************/
    $Config->SetDB("passangerv2");					    // database
    $Config->SetHOST("localhost");					    // database host
    $Config->SetUSER("root");						    // user name
    $Config->SetPWD("");						    // password
    $Config->SetPATHENTITIES("C:/wamp/www/DALGenerator/Entity/");	    // The path where entities will be created
    $Config->SetPATHREPOSITORIES("C:/wamp/www/DALGenerator/Repository/");   // The path where repositories will be created
    /*******
     * END *
     *******/
    
    $Connexion = new fitzlucassen\DALGenerator\Sql($Config->GetDB(), $Config->GetHOST(), $Config->GetUSER(), $Config->GetPWD());
    $Utilities = new fitzlucassen\DALGenerator\Utilities($Connexion, 2);
    $master_array = $Utilities->GetTablesArray();

    $Utilities->CreateClasses($Config->GetPATHENTITIES(), $Config->GetPATHREPOSITORIES());