<?php
    namespace fitzlucassen\DALGenerator;
	
    class Utilities {
	private $_connection;
	private $_master_array;
	private $_two_files;
	
	/**
	 * Constructor
	 * @param PDOConnection $connexion
	 * @param int $two_files 1 if you want only one class to do both roles, 2 if you want two separate files (entity and repository)
	 */
	public function __construct($connexion, $two_files) {
	    $this->_connection = $connexion;
	    $this->_two_files = $two_files;
	}
	
	
	
	/***********
	 * SETTERS *
	 ***********/
	/**
	 * SetMasterArray
	 * @param array $array
	 */
	public function SetMasterArray($array){
	    $this->_master_array = $array;
	}
	
	
	
	/*************
	 * FUNCTIONS *
	 *************/
	/**
	 * GetTablesArray
	 * @param array the name of the tables you don't want to implement
	 * @return array $master_array
	 */
	public function GetTablesArray($ignore_tables = array()){
	    // On récupère toutes les tables de la base voulue
	    $all_tables = $this->_connection->SelectTable("SHOW TABLES FROM " . $this->_connection->GetDB());
	    $master_array = array();

	    // Et pour chacune d'entre elles
	    foreach($all_tables as $thisTable){
		if(in_array($thisTable['Tables_in_passangerv2'], $ignore_tables))
		    continue;
		
		$master_array[$thisTable['Tables_in_passangerv2']] = array();

		// On récupère tous les champs
		$fields = $this->_connection->SelectTable("SHOW FIELDS FROM " . $this->_connection->GetDB() . "." . $thisTable['Tables_in_passangerv2']);

		// Et pour chacun d'entre eux on les ajoute Ã  la table cible
		foreach($fields as $thisField){
		    $master_array[$thisTable['Tables_in_passangerv2']][] = array('label' => $thisField['Field'], 'type' => $thisField['Type']);
		}
	    }
	    $this->SetMasterArray($master_array);

	    return $master_array;
	}

	/**
	 * CreateClasses -> Create all classes
	 */
	public function CreateClasses($pathE, $pathR){
	    foreach($this->_master_array as $key => $value){
		$this->CreateClass($key, $value, $pathE, $pathR);
	    }
	}

	/**
	 * CreateClass -> Create a class thanks to a table name and its fields
	 * @param string $tableName
	 * @param array $tableFields
	 */
	private function CreateClass($tableName, $tableFields, $pathE, $pathR){
	    // On crÃ©Ã©e les fichiers entity et repository
	    if($this->_two_files === 2)
		$entityFile = fopen($pathE . $tableName . ".php", "a+");
	    
	    $repositoryFile = fopen($pathR . $tableName . "Repository.php", "a+");

	    // On commence le code source
	    $sourceEntity = $sourceRepository = "<?php \n" . $this->GetHeaderComment();
	    $sourceEntity .= "\tclass " . ucwords($tableName) . " {\n";
	    $sourceRepository .= "\tclass " . ucwords($tableName) . "Repository {\n";

	    // Et on remplit la classe
	    if($this->_two_files === 2)
		$sourceEntity .= $this->FillEntityAttributs($tableName, $tableFields);
	    else
		$sourceRepository .= $this->FillEntityAttributs($tableName, $tableFields);
	    $sourceRepository .= $this->FillRepositoryAttributs($tableName, $tableFields);

	    if($this->_two_files === 2)
		$sourceEntity .= $this->FillEntityMethods($tableName, $tableFields);
	    else
		$sourceRepository .= $this->FillEntityMethods($tableName, $tableFields);
	    $sourceRepository .= $this->FillRepositoryMethods($tableName, $tableFields);

	    // On finit le code source
	    $sourceEntity .= "\t}\n";
	    $sourceRepository .= "\t}\n";
	    $sourceEntity .= "?>";
	    $sourceRepository .= "?>";

	    // On ecrit le contenu de chaque classe dans leur fichier
	    if($this->_two_files === 2)
		fwrite($entityFile, $sourceEntity);
	    
	    fwrite($repositoryFile, $sourceRepository);

	    // On ferme les deux fichiers
	    if($this->_two_files === 2)
		fclose($entityFile);
	    fclose($repositoryFile);
	}

	/**
	 * FillEntityAttributs -> Return the source for the entity file attributs
	 * @param string $tableName
	 * @param array $tableFields
	 * @return string source code to write
	 */
	private function FillEntityAttributs($tableName, $tableFields){
	    $source = "";

	    foreach($tableFields as $thisField){
		$source .= "\t\tprivate " . '$_' . $thisField['label'] . ";\n";
	    }
	    $source .= "\n";

	    return $source;
	}

	/**
	 * FillRepositoryAttributs -> Return the source for the repository file attributs
	 * @param string $tableName
	 * @param array $tableFields
	 * @return string source code to write
	 */
	private function FillRepositoryAttributs($tableName, $tableFields){
	    $source = "\t\tprivate " . '$_pdo;' . "\n";
	    $source .= "\t\tprivate " . '$_lang;' . "\n\n";

	    return $source;
	}

	/**
	 * FillEntityMethods -> Return the source for the entity file methods
	 * @param string $tableName
	 * @param array $tableFields
	 * @return string source code to write
	 */
	private function FillEntityMethods($tableName, $tableFields){
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
	    if($this->_two_files !== 2){
		$source .= ', $pdo, $lang';
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
	    $source .= "\t\t/***********" . "\n\t\t" . ' * GETTERS *' . "\n\t\t" . ' ***********/' . "\n";
	    foreach($tableFields as $thisField){
		$source .= "\t\tpublic function Get" .ucwords($thisField['label']) . "() {\n";
		$source .= "\t\t\treturn " . '$this->_' . $thisField['label'] . ";\n";
		$source .= "\t\t}\n";
	    }
	    $source .= "\t\t/*******" . "\n\t\t" . ' * END *' . "\n\t\t" . ' *******/' . "\n\n";
	    
	    // Fonction privÃ© pour remplir un objet
	    $source .= "\t\tpublic function FillObject(" . '$properties' . ") {\n";
	    foreach($tableFields as $thisField){
		$source .= "\t\t\t" . '$this->_' . $thisField['label'] . ' = $properties["' . $thisField['label'] . '"];' . "\n";
	    }
	    $source .= "\t\t}\n";

	    return $source;
	}

	/**
	 * FillRepositoryMethods -> Return the source for the repository file methods
	 * @param string $tableName
	 * @param array $tableFields
	 * @return string source code to write
	 */
	private function FillRepositoryMethods($tableName, $tableFields){
	    $source = "";

	    // Constructeur
	    if($this->_two_files === 2){
		$source .= "\t\tpublic function __construct(" . '$pdo, $lang' . "){";
		$source .= "\n\t\t\t" . '$this->_pdo = $pdo;';
		$source .= "\n\t\t\t" . '$this->_lang = $lang;';
		$source .= "\n\t\t}\n\n";
	    }
	    // GetAll
	    $source .= "\t\t/**************************" . "\n\t\t" . ' * REPOSITORIES FUNCTIONS *' . "\n\t\t" . ' **************************/' . "\n";
	    
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
	    $source .= "\t\t}\n";

	    $source .= "\t\t/*******" . "\n\t\t" . ' * END *' . "\n\t\t" . ' *******/' . "\n\n";
	    return $source;
	}
	
	private function GetHeaderComment(){
	    $source= "";
	    
	    $source .= "\t/**********************************************************\n";
	    $source .= "\t **** File generated by fitzlucassen\DALGenerator tool ****\n";
	    $source .= "\t * All right reserved to fitzlucassen repository on github*\n";
	    $source .= "\t ************* https://github.com/fitzlucassen ************\n";
	    $source .= "\t **********************************************************/\n";
	    
	    return $source;
	}
    }