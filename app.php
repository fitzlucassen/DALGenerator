<?php

    require_once 'config.inc.php';
    require_once 'sql.class.php';
    require_once 'utilities.php';

    $Connexion = new Sql(DB, HOST, USER, PWD);

    $master_array = getTablesArray($Connexion);

    createClasses($Connexion, $master_array);