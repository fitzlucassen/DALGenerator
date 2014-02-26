<?php
    namespace fitzlucassen\DALGenerator\library;
	
    class Utilities {
	private $_connection;
	private $_master_array;
	private $_two_files;
	private $_other_attributs;
	private $_host;
	
	/**
	 * Constructor
	 * @param PDOConnection $connexion
	 * @param int $two_files 1 if you want only one class to do both roles, 2 if you want two separate files (entity and repository)
	 */
	public function __construct($connexion, $two_files, $otherAttributs = array(), $host = '') {
	    $this->_connection = $connexion;
	    $this->_two_files = $two_files;
	    $this->_other_attributs = $otherAttributs;
	    $this->_host = $host;
	}
	
	
	
	/***********
	 * SETTERS *
	 ***********/
	/**
	 * SetMasterArray
	 * @param array $array
	 */
	public function setMasterArray($array){
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
	public function getTablesArray($ignore_tables = array()){
	    // On r�cup�re toutes les tables de la base voulue
	    $all_tables = $this->_connection->SelectTable("SHOW TABLES FROM " . $this->_connection->GetDB());
	    $master_array = array();

	    // Et pour chacune d'entre elles
	    foreach($all_tables as $thisTable){
		if(in_array($thisTable['Tables_in_' + $this->_host], $ignore_tables))
		    continue;
		
		$master_array[$thisTable['Tables_in_' + $this->_host]] = array();

		// On r�cup�re tous les champs
		$fields = $this->_connection->SelectTable("SHOW FIELDS FROM " . $this->_connection->GetDB() . "." . $thisTable['Tables_in_' + $this->_host]);

		// Et pour chacun d'entre eux on les ajoute à la table cible
		foreach($fields as $thisField){
		    $master_array[$thisTable['Tables_in_' + $this->_host]][] = array('label' => $thisField['Field'], 'type' => $thisField['Type']);
		}
	    }
	    $this->SetMasterArray($master_array);

	    return $master_array;
	}

	/**
	 * CreateClasses -> Create all classes
	 */
	public function createClasses($pathE, $pathR, $link){
	    foreach($this->_master_array as $key => $value){
		$this->CreateClass($key, $value, $pathE, $pathR, $link);
	    }
	}

	/**
	 * CreateClass -> Create a class thanks to a table name and its fields
	 * @param string $tableName
	 * @param array $tableFields
	 */
	private function createClass($tableName, $tableFields, $pathE, $pathR, $link){
	    // On créée les fichiers entity et repository
	    if($this->_two_files === 2)
		$entityFile = fopen($pathE . ucwords($tableName) . ".php", "a+");
	    
	    $repositoryFile = fopen($pathR . ucwords($tableName) . "Repository.php", "a+");

	    // On commence le code source
	    $sourceEntity = $sourceRepository = "<?php " . FileManager::getBackSpace() . $this->getHeaderComment();
	    $sourceEntity .= FileManager::getTab() . "class " . ucwords($tableName) . " {" . FileManager::getBackSpace();
	    $sourceRepository .= FileManager::getTab() . "class " . ucwords($tableName) . "Repository {" . FileManager::getBackSpace();

	    // Et on remplit la classe
	    if($this->_two_files === 2)
		$sourceEntity .= $this->fillEntityAttributs($tableName, $tableFields, $link);
	    else
		$sourceRepository .= $this->fillEntityAttributs($tableName, $tableFields, $link);
	    $sourceRepository .= $this->fillRepositoryAttributs($tableName, $tableFields);

	    if($this->_two_files === 2)
		$sourceEntity .= $this->fillEntityMethods($tableName, $tableFields, $link);
	    else
		$sourceRepository .= $this->fillEntityMethods($tableName, $tableFields, $link);
	    $sourceRepository .= $this->fillRepositoryMethods($tableName, $tableFields);

	    // On finit le code source
	    $sourceEntity .= FileManager::getTab() . "}" . FileManager::getBackSpace() . "?>";
	    $sourceRepository .= FileManager::getTab() . "}" . FileManager::getBackSpace() . "?>";

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
	private function fillEntityAttributs($tableName, $tableFields, $link){
	    $source = "";
	    $replaceIdByObject = array_key_exists($tableName, $link);
    	    foreach($tableFields as $thisField){
		if($replaceIdByObject && in_array(strtolower(str_replace("id", "", $thisField['label'])), array_keys($link[$tableName])) && strpos($thisField['label'], "id") !== false){	
		    $source .= FileManager::getTab(2) . 'private $_' . strtolower(str_replace("id", "", $thisField['label'])) . ';' . FileManager::getBackSpace();
		}
		$source .= FileManager::getTab(2) . 'private $_' . $thisField['label'] . ';' . FileManager::getBackSpace();
	    }
	    
	    if($replaceIdByObject){
		foreach($link[$tableName] as $key => $value){
		    if($value == "OneToMany"){
			$source .= FileManager::getTab(2) . 'private $_' . $key . 's;' . FileManager::getBackSpace();
		    }
		}
	    }
	    $source .= FileManager::getBackSpace();
		
	    return $source;
	}

	/**
	 * FillRepositoryAttributs -> Return the source for the repository file attributs
	 * @param string $tableName
	 * @param array $tableFields
	 * @return string source code to write
	 */
	private function fillRepositoryAttributs($tableName, $tableFields){
	    $source = FileManager::getTab(2) . 'private $_pdo;' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(2) . 'private $_lang;' . FileManager::getBackSpace();
	    
	    foreach ($this->_other_attributs as $thisOther){
		$source .= FileManager::getTab(2) . 'private $' . $thisOther . ';' . FileManager::getBackSpace();
	    }
	    $source .= FileManager::getBackSpace();
		    
	    return $source;
	}

	/**
	 * FillEntityMethods -> Return the source for the entity file methods
	 * @param string $tableName
	 * @param array $tableFields
	 * @return string source code to write
	 */
	private function fillEntityMethods($tableName, $tableFields, $link){
	    $source = "";
	    $replaceIdByObject = array_key_exists($tableName, $link);
	    
	    // Constructeur
	    $source .= FileManager::getTab(2) . "public function __construct(";
	    $cpt = 0;
	    
	    if($this->_two_files !== 2){
		$source .= '$pdo, $lang, ';
	    }
	    // Paramètres du constructeur
	    foreach($tableFields as $thisField){
		$source .= '$' . $thisField['label'] . ' = ""';
		if($cpt < count($tableFields)-1)
		    $source .= ', ';
		$cpt++;
	    }
	    $source .= "){" . FileManager::getBackSpace();
	    $source .= FileManager::getTab(3) . '$this->fillObject(array(';
	    
	    $cpt = 0;
	    // Le remplissage de l'entity du constructeur
	    foreach($tableFields as $thisField){
		$source .= '"' . $thisField['label'] . '" => $' . $thisField['label'];
		if($cpt < count($tableFields)-1)
		    $source .= ', ';
		$cpt++;
	    }
	    $source .= "));" . FileManager::getBackSpace() . FileManager::getTab(2) . "}" . FileManager::getBackSpace(2);

	    
	    
	    // Commentaire start getter
	    $source .=	FileManager::getTab(2) . FileManager::getComment(11, true) . FileManager::getBackSpace() . 
			FileManager::getTab(2) . ' * GETTERS *' . FileManager::getBackSpace() . 
			FileManager::getTab(2) . FileManager::getComment(11, false) . FileManager::getBackSpace();
	    
	    // Getters publiques
	    foreach($tableFields as $thisField){
		if($replaceIdByObject && in_array(strtolower(str_replace("id", "", $thisField['label'])), array_keys($link[$tableName])) && strpos($thisField['label'], "id") !== false){
		    $attribut = strtolower(str_replace("id", "", $thisField['label']));
		    $linking = $link[$tableName][$attribut];
		    
		    if($linking == "OneToOne"){
			$source .= FileManager::getTab(2) . FileManager::getPrototype("get" . ucwords(str_replace("id", "", $thisField['label']))) . "() {" . FileManager::getBackSpace();
			$source .= FileManager::getTab(3) . '$query = "SELECT * FROM ' . $attribut . ' WHERE id' . ucwords($attribut) . '=" . $this->_id' . ucwords($attribut) . ';' . FileManager::getBackSpace();
			$source .= FileManager::getTab(3) . 'try {' . FileManager::getBackSpace();
			$source .= FileManager::getTab(4) . 'return $this->_pdo->Select($query);' . FileManager::getBackSpace();
			$source .= FileManager::getTab(3) . '}' . FileManager::getBackSpace() . FileManager::getTab(3) .  "catch(PDOException " . '$e){' . FileManager::getBackSpace();
			$source .= FileManager::getTab(4) . 'print $e->getMessage();' . FileManager::getBackSpace() . FileManager::getTab(3) ."}" . FileManager::getBackSpace();
			$source .= FileManager::getTab(3) . 'return array();' . FileManager::getBackSpace();
			$source .= FileManager::getTab(2) . '}' . FileManager::getBackSpace(2);
		    }
		}
		$source .=  FileManager::getTab(2) . FileManager::getPrototype("get" . ucwords($thisField['label'])) . "() {" . FileManager::getBackSpace() .
			    FileManager::getTab(3) . 'return $this->_' . $thisField['label'] . ';' . FileManager::getBackSpace() .
			    FileManager::getTab(2) . '}' . FileManager::getBackSpace();
	    }
	    // Get OneToMany Objects
	    if($replaceIdByObject){
		foreach($link[$tableName] as $key => $value){
		    if($value == "OneToMany"){
			$source .= FileManager::getTab(2) . FileManager::getPrototype("get" . ucwords($key)) . "s() {" . FileManager::getBackSpace();
			$source .= FileManager::getTab(3) . '$query = "SELECT * FROM ' . $key . ' WHERE id' . ucwords($tableName) . '=" . $this->_id;' . FileManager::getBackSpace();
			$source .= FileManager::getTab(3) . 'try {' . FileManager::getBackSpace();
			$source .= FileManager::getTab(4) . 'return $this->_pdo->SelectTable($query);' . FileManager::getBackSpace();
			$source .= FileManager::getTab(3) . '}' . FileManager::getBackSpace() . FileManager::getTab(3) .  "catch(PDOException " . '$e){' . FileManager::getBackSpace();
			$source .= FileManager::getTab(4) . 'print $e->getMessage();' . FileManager::getBackSpace() . FileManager::getTab(3) ."}" . FileManager::getBackSpace();
			$source .= FileManager::getTab(3) . 'return array();' . FileManager::getBackSpace();
			$source .= FileManager::getTab(2) . '}' . FileManager::getBackSpace(2);
		    }
		}
	    }
	    // Commentaire end getter
	    $source .=	FileManager::getTab(2) . FileManager::getComment(7, true) . FileManager::getBackSpace() . 
			FileManager::getTab(2) . ' * END *' . FileManager::getBackSpace() . 
			FileManager::getTab(2) . FileManager::getComment(7, false) . FileManager::getBackSpace(2);
	    
	    
	    // Fonction privé pour remplir un objet
	    $source .= FileManager::getTab(2) . FileManager::getPrototype("fillObject") . '($properties) {' . FileManager::getBackSpace();
	    foreach($tableFields as $thisField){
		$source .= FileManager::getTab(3) . 'if(!empty($properties["' . $thisField['label'] . '"]))' . FileManager::getBackSpace();
		$source .= FileManager::getTab(4) . '$this->_' . $thisField['label'] . ' = $properties["' . $thisField['label'] . '"];' . FileManager::getBackSpace();
	    }
	    $source .= FileManager::getTab(2) . "}" . FileManager::getBackSpace();

	    return $source;
	}

	/**
	 * FillRepositoryMethods -> Return the source for the repository file methods
	 * @param string $tableName
	 * @param array $tableFields
	 * @return string source code to write
	 */
	private function fillRepositoryMethods($tableName, $tableFields){
	    $source = "";

	    // Constructeur
	    if($this->_two_files === 2){
		$source .= FileManager::getTab(2) . FileManager::getPrototype("__construct") . '($pdo, $lang){' . FileManager::getBackSpace();
		
		if(count($this->_other_attributs) > 0){
		    foreach ($this->_other_attributs as $thisOther){
			if($thisOther == '_pdoHelper'){
			    $source .= FileManager::getTab(3) . '$this->' . $thisOther . ' = $pdo;' . FileManager::getBackSpace();
			    $source .= FileManager::getTab(3) . '$this->_pdo = $pdo->GetConnection();' . FileManager::getBackSpace();
			}
		    }
		    $source .= FileManager::getTab(3) . '$this->_lang = $lang;' . FileManager::getBackSpace();
		}
		else {
		    $source .= FileManager::getTab(3) . '$this->_pdo = $pdo;' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(3) . '$this->_lang = $lang;' . FileManager::getBackSpace();
		}
		$source .= FileManager::getTab(2) . '}' . FileManager::getBackSpace(2);
	    }
	    // GetAll
	    $source .=	FileManager::getTab(2) . FileManager::getComment(26, true) . FileManager::getBackSpace() . 
			FileManager::getTab(2) . ' * REPOSITORIES FUNCTIONS *' . FileManager::getBackSpace() . 
			FileManager::getTab(2) . FileManager::getComment(26, false) . FileManager::getBackSpace();
	    
	    $source .= FileManager::getTab(2) . FileManager::getPrototype("getAll") . "(){" . FileManager::getBackSpace();
	    $source .= FileManager::getTab(3) . '$query = "SELECT * FROM ' . $tableName . '";' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(3) . 'try {' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(4) . 'return $this->_pdo->SelectTable($query);' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(3) . '}' . FileManager::getBackSpace() . FileManager::getTab(3) .  "catch(PDOException " . '$e){' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(4) . 'print $e->getMessage();' . FileManager::getBackSpace() . FileManager::getTab(3) ."}" . FileManager::getBackSpace();
	    $source .= FileManager::getTab(3) . 'return array();' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(2) . '}' . FileManager::getBackSpace(2);

	    // GetById
	    $source .= FileManager::getTab(2) . FileManager::getPrototype("getById") . '($id){' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(3) . '$query = "SELECT * FROM ' . $tableName . ' WHERE id=" . $id;' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(3) . 'try {' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(4) . '$properties = $this->_pdo->Select($query);' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(4) . '$object = new ' . ucwords($tableName) . '();' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(4) . '$object->fillObject($properties);' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(4) . 'return $object;' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(3) . '}' . FileManager::getBackSpace() . FileManager::getTab(3) . 'catch(PDOException $e){' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(4) . 'print $e->getMessage();' . FileManager::getBackSpace() . FileManager::getTab(3) . "}" . FileManager::getBackSpace();
	    $source .= FileManager::getTab(3) . 'return array();' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(2) . '}' . FileManager::getBackSpace(2);

	    // Delete
	    $source .= FileManager::getTab(2) . FileManager::getPrototype("delete") . '($id) {' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(3) . '$query = "DELETE FROM ' . $tableName . ' WHERE id=" . $id;' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(3) . 'try {' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(4) . 'return $this->_pdo->Query($query);' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(3) . '}' . FileManager::getBackSpace() . FileManager::getTab(3) . 'catch(PDOException $e){' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(4) . 'print $e->getMessage();' . FileManager::getBackSpace() . FileManager::getTab(3) . '}' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(3) . 'return array();' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(2) . '}' . FileManager::getBackSpace(2);

	    // Add
	    $source .= FileManager::getTab(2) . FileManager::getPrototype("add") . '($properties) {' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(3) . '$query = "INSERT INTO ' . $tableName . '(';
	    $cpt = 0;
	    foreach($tableFields as $thisField){
		$source .= "'" . $thisField['label'] . "'";
		if($cpt < count($tableFields)-1)
		    $source .= ', ';
		$cpt++;
	    }
	    $source .= ')' . FileManager::getBackSpace() . FileManager::getTab(4) . 'VALUES(';

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
	    $source .= ')";' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(3) . 'try {' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(4) . 'return $this->_pdo->Query($query);' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(3) . '}' . FileManager::getBackSpace() . FileManager::getTab(3) . 'catch(PDOException $e){' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(4) . 'print $e->getMessage();' . FileManager::getBackSpace() . FileManager::getTab(3) . '}' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(3) . 'return array();' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(2) . '}' . FileManager::getBackSpace(2);

	    // Update
	    $source .= FileManager::getTab(2) . FileManager::getPrototype("update") . '($id, $properties) {' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(3) . '$query = "UPDATE ' . $tableName . " " . FileManager::getBackSpace();
	    $source .= FileManager::getTab(4) . 'SET ';

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
	    $source .= FileManager::getBackSpace() . FileManager::getTab(4) . 'WHERE id=" . $id;' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(3) . 'try {' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(4) . 'return $this->_pdo->Query($query);' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(3) . '}' . FileManager::getBackSpace() . FileManager::getTab(3) . 'catch(PDOException $e){' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(4) . 'print $e->getMessage();' . FileManager::getBackSpace() . FileManager::getTab(3) . '}' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(3) . 'return array();' . FileManager::getBackSpace();
	    $source .= FileManager::getTab(2) . '}' . FileManager::getBackSpace();

	    $source .=	FileManager::getTab(2) . FileManager::getComment(7, true) . FileManager::getBackSpace() . 
			FileManager::getTab(2) . ' * END *' . FileManager::getBackSpace() . FileManager::getTab(2) . 
			FileManager::getComment(7, false) . FileManager::getBackSpace(2);
	    return $source;
	}
	
	private function getHeaderComment(){
	    $source= "";
	    
	    $source .= FileManager::getTab() . FileManager::getComment(58, true) . FileManager::getBackSpace();
	    $source .= FileManager::getTab() . " **** File generated by fitzlucassen\DALGenerator tool ****" . FileManager::getBackSpace();
	    $source .= FileManager::getTab() . " * All right reserved to fitzlucassen repository on github*" . FileManager::getBackSpace();
	    $source .= FileManager::getTab() . " ************* https://github.com/fitzlucassen ************" . FileManager::getBackSpace();
	    $source .= FileManager::getTab() . FileManager::getComment(58, false) . FileManager::getBackSpace();
	    
	    return $source;
	}
    }