<?php

    function getTablesArray($Connexion){
	// On récupère toutes les tables de la base voulue
	$all_tables = $Connexion->SelectTable("SHOW TABLES FROM " . $Connexion->GetDB());
	$master_array = array();

	// Et pour chacune d'entre elles
	foreach($all_tables as $thisTable){
	    $master_array[$thisTable['Tables_in_passangerv2']] = array();

	    // On récupère tous les champs
	    $fields = $Connexion->SelectTable("SHOW FIELDS FROM " . $Connexion->GetDB() . "." . $thisTable['Tables_in_passangerv2']);

	    // Et pour chacun d'entre eux on les ajoute à la table cible
	    foreach($fields as $thisField){
		$master_array[$thisTable['Tables_in_passangerv2']][] = array('label' => $thisField['Field'], 'type' => $thisField['Type']);
	    }
	}

	return $master_array;
    }

    function createClasses($Connexion, $master_array){
	foreach($master_array as $key => $value){
	    createClass($key, $value);
	}
    }

    function createClass($tableName, $tableFields){
	// On créée les fichiers entity et repository
	$entityFile = fopen(PATH_ENTITIES . $tableName . ".php", "a+");
	$repositoryFile = fopen(PATH_REPOSITORIES . $tableName . "Repository.php", "a+");

	// On commence le code source
	$sourceEntity = $sourceRepository = "<?php \n";
	$sourceEntity .= "\tclass " . ucwords($tableName) . " {\n";
	$sourceRepository .= "\tclass " . ucwords($tableName) . "Repository {\n";

	// Et on remplit la classe
	$sourceEntity .= fillEntityAttributs($tableName, $tableFields);
	$sourceRepository .= fillRepositoryAttributs($tableName, $tableFields);

	$sourceEntity .= fillEntityMethods($tableName, $tableFields);
	$sourceRepository .= fillRepositoryMethods($tableName, $tableFields);

	// On finit le code source
	$sourceEntity .= "\t}\n";
	$sourceRepository .= "\t}\n";
	$sourceEntity .= "?>";
	$sourceRepository .= "?>";

	// On ecrit le contenu de chaque classe dans leur fichier
	fwrite($entityFile, $sourceEntity);
	fwrite($repositoryFile, $sourceRepository);

	// On ferme les deux fichiers
	fclose($entityFile);
	fclose($repositoryFile);
    }

    function fillEntityAttributs($tableName, $tableFields){
	$source = "";

	foreach($tableFields as $thisField){
	    $source .= "\t\tprivate " . '$_' . $thisField['label'] . ";\n";
	}
	$source .= "\n";

	return $source;
    }

    function fillRepositoryAttributs($tableName, $tableFields){
	$source = "\t\tprivate " . '$_pdo;' . "\n";
	$source .= "\t\tprivate " . '$_lang;' . "\n\n";
	
	return $source;
    }

    function fillEntityMethods($tableName, $tableFields){
	$source = "";

	// Constructeur
	$source .= "\t\tpublic function __construct(";
	$cpt = 0;
	foreach($tableFields as $thisField){
	    $source .= '$' . $thisField['label'];
	    if($cpt < count($tableFields)-1)
		$source .= ', ';
	    $cpt++;
	}
	$source .= "){\n";
	$source .= "\t\t\tFillObject(array(";

	$cpt = 0;
	foreach($tableFields as $thisField){
	    $source .= '"' . $thisField['label'] . '" => $' . $thisField['label'];
	    if($cpt < count($tableFields)-1)
		$source .= ', ';
	    $cpt++;
	}
	$source .= "));\n";
	$source .= "\t\t}\n\n";

	// Getters publiques
	foreach($tableFields as $thisField){
	    $source .= "\t\tpublic function Get" .ucwords($thisField['label']) . "() {\n";
	    $source .= "\t\t\treturn " . '$this->_' . $thisField['label'] . ";\n";
	    $source .= "\t\t}\n\n";
	}

	// Fonction privé pour remplir un objet
	$source .= "\t\tpublic function FillObject(" . '$properties' . ") {\n";
	foreach($tableFields as $thisField){
	    $source .= "\t\t\t" . '$this->_' . $thisField['label'] . ' = $properties["' . $thisField['label'] . '"];' . "\n";
	}
	$source .= "\t\t}\n";

	return $source;
    }

    function fillRepositoryMethods($tableName, $tableFields){
	$source = "";
	
	// Constructeur
	$source .= "\t\tpublic function __construct(" . '$pdo, $lang' . "){";
	$source .= "\n\t\t\t" . '$this->_pdo = $pdo;';
	$source .= "\n\t\t\t" . '$this->_lang = $lang;';
	$source .= "\n\t\t}\n\n";
	
	// GetAll
	$source .= "\t\tpublic function GetAll(){\n";
	$source .= "\t\t\t" . '$query = "SELECT * FROM ' . $tableName . '";' . "\n";
	$source .= "\t\t\ttry {\n";
	$source .= "\t\t\t\treturn " . '$this->_pdo->SelectTable($query);' . "\n";
	$source .= "\t\t\t}\n\t\t\tcatch(PDOException " . '$e){' . "\n";
	$source .= "\t\t\t\tprint " . '$e->getMessage();' . "\n\t\t\t}\n";
	$source .= "\t\t\treturn array();\n";
	$source .= "\t\t}\n\n";
	
	// GetById
	$source .= "\t\tpublic function GetById(" . '$id' . "){\n";
	$source .= "\t\t\t" . '$query = "SELECT * FROM ' . $tableName . ' WHERE id=" . $id;' . "\n";
	$source .= "\t\t\ttry {\n";
	$source .= "\t\t\t\t" . '$properties = $this->_pdo->Select($query);' . "\n";
	$source .= "\t\t\t\t" . '$object = new ' . ucwords($tableName) . '();' . "\n";
	$source .= "\t\t\t\t" . '$object->FillObject($properties);' . "\n";
	$source .= "\t\t\t\t" . 'return $object;' . "\n";
	$source .= "\t\t\t}\n\t\t\tcatch(PDOException " . '$e){' . "\n";
	$source .= "\t\t\t\tprint " . '$e->getMessage();' . "\n\t\t\t}\n";
	$source .= "\t\t\treturn array();\n";
	$source .= "\t\t}\n\n";
	
	// Delete
	$source .= "\t\tpublic function Delete(" . '$id' . ") {\n";
	$source .= "\t\t\t" . '$query = "DELETE FROM ' . $tableName . ' WHERE id=" . $id;' . "\n";
	$source .= "\t\t\ttry {\n";
	$source .= "\t\t\t\treturn " . '$this->_pdo->Query($query);' . "\n";
	$source .= "\t\t\t}\n\t\t\tcatch(PDOException " . '$e){' . "\n";
	$source .= "\t\t\t\tprint " . '$e->getMessage();' . "\n\t\t\t}\n";
	$source .= "\t\t\treturn array();\n";
	$source .= "\t\t}\n\n";
	
	// Add
	$source .= "\t\tpublic function Add(" . '$properties' . ") {\n";
	$source .= "\t\t\t" . '$query = "INSERT INTO ' . $tableName . '(';
	$cpt = 0;
	foreach($tableFields as $thisField){
	    $source .= "'" . $thisField['label'] . "'";
	    if($cpt < count($tableFields)-1)
		$source .= ', ';
	    $cpt++;
	}
	$source .= ")\n";
	$source .= "\t\t\t\tVALUES(";
	
	$cpt = 0;
	foreach($tableFields as $thisField){
	    if(strpos($thisField['type'], 'text') !== false || strpos($thisField['type'], 'varchar') !== false || strpos($thisField['type'], 'date') !== false)
		$source .= "'" . '" . ' . '$properties["' . $thisField['label'] . '"]' . ' . "' . "'";
	    else
		$source .= '" . ' . '$properties["' . $thisField['label'] . '"]' . ' . "';
		
	    if($cpt < count($tableFields)-1)
		$source .= ', ';
	    $cpt++;
	}
	$source .= ")" . '";' . "\n";
	$source .= "\t\t\ttry {\n";
	$source .= "\t\t\t\treturn " . '$this->_pdo->Query($query);' . "\n";
	$source .= "\t\t\t}\n\t\t\tcatch(PDOException " . '$e){' . "\n";
	$source .= "\t\t\t\tprint " . '$e->getMessage();' . "\n\t\t\t}\n";
	$source .= "\t\t\treturn array();\n";
	$source .= "\t\t}\n\n";
	
	// Update
	$source .= "\t\tpublic function Update(" . '$id, $properties' . ") {\n";
	$source .= "\t\t\t" . '$query = "UPDATE ' . $tableName . " \n";
	$source .= "\t\t\t\tSET ";
	
	$cpt = 0;
	foreach($tableFields as $thisField){
	    $source .= $thisField['label'] . " = ";
	    if(strpos($thisField['type'], 'text') !== false || strpos($thisField['type'], 'varchar') !== false || strpos($thisField['type'], 'date') !== false)
		$source .= "'" . '" . ' . '$properties["' . $thisField['label'] . '"]' . ' . "' . "'";
	    else
		$source .= '" . ' . '$properties["' . $thisField['label'] . '"]' . ' . "';
		
	    if($cpt < count($tableFields)-1)
		$source .= ', ';
	    $cpt++;
	}
	$source .= "\n\t\t\t\tWHERE id=" . '" . $id;' . "\n";
	$source .= "\t\t\ttry {\n";
	$source .= "\t\t\t\treturn " . '$this->_pdo->Query($query);' . "\n";
	$source .= "\t\t\t}\n\t\t\tcatch(PDOException " . '$e){' . "\n";
	$source .= "\t\t\t\tprint " . '$e->getMessage();' . "\n\t\t\t}\n";
	$source .= "\t\t\treturn array();\n";
	$source .= "\t\t}\n\n";
	
	return $source;
    }