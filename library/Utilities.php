<?php
    namespace fitzlucassen\DALGenerator\library;
	
    class Utilities {
		private $_connection;
		private $_master_array;
		private $_other_attributs;
		private $_host;
		
		/**
		 * Constructor
		 * @param PDOConnection $connexion
		 */
		public function __construct($connexion, $otherAttributs = array(), $host = '') {
		    $this->_connection = $connexion;
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
		public function createClasses($path, $link){
		    foreach($this->_master_array as $key => $value){
				$this->CreateClass($key, $value, $path, $link);
		    }
		}

		/**
		 * CreateClass -> Create a class thanks to a table name and its fields
		 * @param string $tableName
		 * @param array $tableFields
		 */
		private function createClass($tableName, $tableFields, $path, $link){
		    // On commence le code source
		    $sourceRepository = $this->getRepositoryHeader($tableName);
		    $sourceEntity = $this->getEntityHeader($tableName);

		    // Et on remplit la classe
			$sourceEntity .= $this->fillEntityAttributs($tableName, $tableFields, $link);
		    $sourceRepository .= $this->fillRepositoryAttributs($tableName, $tableFields);

			$sourceEntity .= $this->fillEntityMethods($tableName, $tableFields, $link);
		    $sourceRepository .= $this->fillRepositoryMethods($tableName, $tableFields);

		    // On finit le code source
		    $sourceEntity .= FileManager::getTab() . "}" . FileManager::getBackSpace();
		    $sourceRepository .= FileManager::getTab() . "}" . FileManager::getBackSpace();

		    FileManager::createDirectoryIfNotExist($path . '/Base');
		    FileManager::createDirectoryIfNotExist($path . '/Base/Entity');
		    FileManager::createDirectoryIfNotExist($path . '/Base/Repository');
		    FileManager::createDirectoryIfNotExist($path . '/Entity');
		    FileManager::createDirectoryIfNotExist($path . '/Repository');

		    // On créée les fichiers entity et repository base
		    $entityBaseFile = FileManager::createFile($path . 'Base/Entity/' . ucwords(strtolower($tableName)) . "Base.php", "w+", true);
		    $repositoryBaseFile = FileManager::createFile($path . 'Base/Repository/' . ucwords(strtolower($tableName)) . "RepositoryBase.php", "w+", true);
		    // On créée les fichiers entity et repository normaux uniquement s'ils n'existent pas déjà
		    $entityFile = FileManager::createFile($path . 'Entity/' . ucwords(strtolower($tableName)) . ".php", "w+", false);
		    $repositoryFile = FileManager::createFile($path . 'Repository/' . ucwords(strtolower($tableName)) . "Repository.php", "w+", false);+

		    $sourceRepository2 = $this->getRepositoryHeader($tableName, false, ucwords(strtolower($tableName)) . 'RepositoryBase');
		    $sourceEntity2 = $this->getEntityHeader($tableName, false, ucwords(strtolower($tableName)) . 'Base');

		    $sourceRepository2 .= $this->getRepositoryContent();
		    $sourceEntity2 .= $this->getEntityContent($tableName, $tableFields, $link);

		    $sourceRepository2 .= FileManager::getTab() . "}" . FileManager::getBackSpace();
		    $sourceEntity2.= FileManager::getTab() . "}" . FileManager::getBackSpace();

		    // On ecrit le contenu de chaque classe dans leur fichier
		    FileManager::writeInFiles(array(
		    	$sourceEntity => $entityBaseFile,
		    	$sourceRepository => $repositoryBaseFile,
		    	$sourceEntity2 => $entityFile,
		    	$sourceRepository2 => $repositoryFile,
		    ));

		    // On ferme les quatres fichiers
		    FileManager::closeFiles(array($entityFile, $repositoryFile, $entityBaseFile, $repositoryBaseFile));
		}

		private function getRepositoryHeader($tableName, $base = true, $extends = false){
			$string = '';

			$string .= "<?php " . FileManager::getBackSpace() . $this->getHeaderComment();
			$string .= FileManager::getTab() . 'namespace fitzlucassen\FLFramework\Data\\' . ($base ? 'Base\\' : '') . 'Repository;' . FileManager::getBackSpace(2);
		    $string .= FileManager::getTab() . 'use fitzlucassen\FLFramework\Library\Core;' . FileManager::getBackSpace();
		    $string .= FileManager::getTab() . 'use fitzlucassen\FLFramework\Data\Entity;' . FileManager::getBackSpace();
	    	$string .= FileManager::getTab() . 'use fitzlucassen\FLFramework\Data\Base\Entity as EntityBase;' . FileManager::getBackSpace();

	    	if(!$base)
	    		$string .= FileManager::getTab() . 'use fitzlucassen\FLFramework\Data\Base\Repository as RepositoryBase;' . FileManager::getBackSpace();

		    $extends = ($extends !== false ? 'extends ' . (!$base ? 'RepositoryBase\\' : '') . $extends : '');

	    	$string .= FileManager::getBackSpace();
		    $string .= FileManager::getTab() . 'class ' . ucwords(strtolower($tableName)) . 'Repository' . ($base ? 'Base' : '') . ' ' . $extends . ' {' . FileManager::getBackSpace();

			return $string;
		}

		private function getEntityHeader($tableName, $base = true, $extends = false){
			$string = '';

			$string .= "<?php " . FileManager::getBackSpace() . $this->getHeaderComment();
			$string .= FileManager::getTab() . 'namespace fitzlucassen\FLFramework\Data\\' . ($base ? 'Base\\' : '') . 'Entity;' . FileManager::getBackSpace(2);
		    $string .= FileManager::getTab() . 'use fitzlucassen\FLFramework\Library\Core;' . FileManager::getBackSpace();
		    if(!$base)
		    	$string .= FileManager::getTab() . 'use fitzlucassen\FLFramework\Data\Base\Entity as EntityBase;' . FileManager::getBackSpace();
		    else
		    	$string .= FileManager::getTab() . 'use fitzlucassen\FLFramework\Data\Entity;' . FileManager::getBackSpace();

		    $extends = ($extends !== false ? 'extends ' . (!$base ? 'EntityBase\\' : '') . $extends : '');

		    $string .= FileManager::getBackSpace();
		    $string .= FileManager::getTab() . 'class ' . ucwords(strtolower($tableName)) . ($base ? 'Base' : '') . ' ' . $extends . ' {' . FileManager::getBackSpace();

			return $string;
		}

		private function getRepositoryContent(){
			$source = "";

		    // Constructeur
			$source .= FileManager::getTab(2) . FileManager::getPrototype("__construct", array('pdo' => '_none_', 'lang' => '_none_')) . ' {' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(3) . 'parent::__construct($pdo, $lang);' . FileManager::getBackSpace();
			$source .= FileManager::getTab(2) . '}' . FileManager::getBackSpace(2);
		    
		    return $source;
		}

		private function getEntityContent($tableName, $tableFields, $link){
		    $source = "";
		    $replaceIdByObject = array_key_exists($tableName, $link);

		    // Constructeur
		    $paramsTmp = array();
		    $cpt = 0;
		    
		    // Paramètres du constructeur
		    foreach($tableFields as $thisField){
		    	$paramsTmp[$thisField['label']] = "''";
				$cpt++;
		    }

		    $source .= FileManager::getTab(2) . FileManager::getPrototype('__construct', $paramsTmp);
		    $source .= "{" . FileManager::getBackSpace();

		    $source .= FileManager::getTab(3) . 'parent::__construct(';

		    $cpt = 0;
		    foreach ($paramsTmp as $key => $value) {
		    	$source .= '$' . $key;
		    	if($cpt < count($paramsTmp) - 1)
				    $source .= ', ';
		    	$cpt++;
		    }
		    $source .= ');' . FileManager::getBackSpace();
			$source .= FileManager::getTab(2) . '}' . FileManager::getBackSpace(2);
		    
		    return $source;
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
		    foreach ($this->_other_attributs as $thisOther){
				if($thisOther == '_queryBuilder'){
				    $source .= FileManager::getTab(2) . 'private $' . $thisOther . ';' . FileManager::getBackSpace();
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
		    $source = FileManager::getTab(2) . 'protected $_pdo;' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(2) . 'protected $_lang;' . FileManager::getBackSpace();
		    
		    foreach ($this->_other_attributs as $thisOther){
				$source .= FileManager::getTab(2) . 'protected $' . $thisOther . ';' . FileManager::getBackSpace();
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
		    $paramsTmp = array();
		    $cpt = 0;
		    
		    // Paramètres du constructeur
		    foreach($tableFields as $thisField){
		    	$paramsTmp[$thisField['label']] = "''";
				$cpt++;
		    }

		    $source .= FileManager::getTab(2) . FileManager::getPrototype('__construct', $paramsTmp);
		    $source .= "{" . FileManager::getBackSpace();
		    
		    // Construct queryBuilder
		    foreach ($this->_other_attributs as $thisOther){
				if($thisOther == '_queryBuilder'){
				    $source .= FileManager::getTab(3) . '$this->' . $thisOther . ' = new Core\QueryBuilder(true);' . FileManager::getBackSpace();
				}
		    }
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
						$source .= FileManager::getTab(2) . FileManager::getPrototype("get" . ucwords(str_replace("id", "", $thisField['label'])), array('repository' => '_none_')) . " {" . FileManager::getBackSpace();
						$source .= FileManager::getTab(3) . '$result = $repository->getById($this->_' . $thisField['label'] . ');' . FileManager::getBackSpace();
						$source .= FileManager::getTab(3) . 'return $result;' . FileManager::getBackSpace();
						$source .= FileManager::getTab(2) . '}' . FileManager::getBackSpace(2);
				    }
				}
				$source .=  FileManager::getTab(2) . FileManager::getPrototype("get" . ucwords($thisField['label'])) . " {" . FileManager::getBackSpace() .
						    FileManager::getTab(3) . 'return $this->_' . $thisField['label'] . ';' . FileManager::getBackSpace() .
						    FileManager::getTab(2) . '}' . FileManager::getBackSpace();
		    }
		    // Get OneToMany Objects
		    if($replaceIdByObject){
				foreach($link[$tableName] as $key => $value){
				    if($value == "OneToMany"){
						$source .= FileManager::getTab(2) . FileManager::getPrototype("get" . ucwords($key) . 's', array('repository' => '_none_')) . " {" . FileManager::getBackSpace();
						$source .= FileManager::getTab(3) . '$result = $repository->getBy("id' . ucwords($tableName) . '", $this->_id);' . FileManager::getBackSpace();
						$source .= FileManager::getTab(3) . 'return $result;' . FileManager::getBackSpace();
						$source .= FileManager::getTab(2) . '}' . FileManager::getBackSpace(2);
				    }
				}
		    }
		    // Commentaire end getter
		    $source .=	FileManager::getTab(2) . FileManager::getComment(7, true) . FileManager::getBackSpace() . 
						FileManager::getTab(2) . ' * END *' . FileManager::getBackSpace() . 
						FileManager::getTab(2) . FileManager::getComment(7, false) . FileManager::getBackSpace(2);
		    
		    // Fonction privé pour remplir un objet
		    $source .= FileManager::getTab(2) . FileManager::getPrototype("fillObject", array('properties' => '_none_')) . ' {' . FileManager::getBackSpace();
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
			$source .= FileManager::getTab(2) . FileManager::getPrototype("__construct", array('pdo' => '_none_', 'lang' => '_none_')) . ' {' . FileManager::getBackSpace();
			
			if(count($this->_other_attributs) > 0){
			    foreach ($this->_other_attributs as $thisOther){
					if($thisOther == '_pdoHelper'){
					    $source .= FileManager::getTab(3) . '$this->' . $thisOther . ' = $pdo;' . FileManager::getBackSpace();
					    $source .= FileManager::getTab(3) . '$this->_pdo = $pdo->GetConnection();' . FileManager::getBackSpace();
					}
					if($thisOther == '_queryBuilder'){
					    $source .= FileManager::getTab(3) . '$this->' . $thisOther . ' = new Core\QueryBuilder(true);' . FileManager::getBackSpace();
					}
			    }
			    $source .= FileManager::getTab(3) . '$this->_lang = $lang;' . FileManager::getBackSpace();
			}
			else {
			    $source .= FileManager::getTab(3) . '$this->_pdo = $pdo;' . FileManager::getBackSpace();
			    $source .= FileManager::getTab(3) . '$this->_lang = $lang;' . FileManager::getBackSpace();
			}
			$source .= FileManager::getTab(2) . '}' . FileManager::getBackSpace(2);
		    
		    // GetAll
		    $source .=	FileManager::getTab(2) . FileManager::getComment(26, true) . FileManager::getBackSpace() . 
						FileManager::getTab(2) . ' * REPOSITORIES FUNCTIONS *' . FileManager::getBackSpace() . 
						FileManager::getTab(2) . FileManager::getComment(26, false) . FileManager::getBackSpace();
		    
		    $source .= FileManager::getTab(2) . FileManager::getPrototype("getAll", array('Connection' => '_none_'), true, true) . ' {' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(3) . '$qb = new Core\QueryBuilder(true);' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(3) . '$query = $qb->select()->from(array("' . $tableName . '"))->getQuery();' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(3) . 'try {' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(4) . '$result = $Connection->SelectTable($query);' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(4) . '$array = array();' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(4) . 'foreach ($result as $object){' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(5) . '$o = new Entity\\' . ucwords($tableName) . '();' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(5) . '$o->fillObject($object);' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(5) . '$array[] = $o;' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(4) . '}' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(4) . 'return $array;' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(3) . '}' . FileManager::getBackSpace() . FileManager::getTab(3) .  "catch(PDOException " . '$e){' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(4) . 'print $e->getMessage();' . FileManager::getBackSpace() . FileManager::getTab(3) ."}" . FileManager::getBackSpace();
		    $source .= FileManager::getTab(3) . 'return array();' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(2) . '}' . FileManager::getBackSpace(2);

		    // GetById
		    $source .= FileManager::getTab(2) . FileManager::getPrototype("getById", array('id' => '_none_')) . ' {' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(3) . '$query = $this->_queryBuilder->select()->from(array("' . $tableName . '"))' . '
											->where(array(array("link" => "", "left" => "id", "operator" => "=", "right" => $id)))->getQuery();' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(3) . 'try {' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(4) . '$properties = $this->_pdoHelper->Select($query);' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(4) . '$object = new Entity\\' . ucwords($tableName) . '();' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(4) . '$object->fillObject($properties);' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(4) . 'return $object;' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(3) . '}' . FileManager::getBackSpace() . FileManager::getTab(3) . 'catch(PDOException $e){' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(4) . 'print $e->getMessage();' . FileManager::getBackSpace() . FileManager::getTab(3) . "}" . FileManager::getBackSpace();
		    $source .= FileManager::getTab(3) . 'return array();' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(2) . '}' . FileManager::getBackSpace(2);

		    // GetBy
		    $source .= FileManager::getTab(2) . FileManager::getPrototype("getBy", array('key' => '_none_', 'value' => '_none_')) . ' {' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(3) . '$query = $this->_queryBuilder->select()->from(array("' . $tableName . '"))' . '
											->where(array(array("link" => "", "left" => $key, "operator" => "=", "right" => $value)))->getQuery();' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(3) . 'try {' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(4) . '$properties = $this->_pdoHelper->SelectTable($query);' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(4) . '$array = array();' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(4) . 'foreach ($properties as $object){' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(5) . '$o = new Entity\\' . ucwords($tableName) . '();' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(5) . '$o->fillObject($object);' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(5) . '$array[] = $o;' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(4) . '}' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(4) . 'return $array;' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(3) . '}' . FileManager::getBackSpace() . FileManager::getTab(3) .  "catch(PDOException " . '$e){' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(4) . 'print $e->getMessage();' . FileManager::getBackSpace() . FileManager::getTab(3) ."}" . FileManager::getBackSpace();
		    $source .= FileManager::getTab(3) . 'return array();' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(2) . '}' . FileManager::getBackSpace(2);

		    // Delete
		    $source .= FileManager::getTab(2) . FileManager::getPrototype("delete", array('id' => '_none_')) . ' {' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(3) . '$query = $this->_queryBuilder->delete("' . $tableName . '")
											->where(array(array("link" => "", "left" => "id", "operator" => "=", "right" => $id )))
											->getQuery();' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(3) . 'try {' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(4) . 'return $this->_pdo->Query($query);' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(3) . '}' . FileManager::getBackSpace() . FileManager::getTab(3) . 'catch(PDOException $e){' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(4) . 'print $e->getMessage();' . FileManager::getBackSpace() . FileManager::getTab(3) . '}' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(3) . 'return array();' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(2) . '}' . FileManager::getBackSpace(2);

		    // Add
		    $source .= FileManager::getTab(2) . FileManager::getPrototype("add", array('properties' => '_none_')) . ' {' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(3) . '$query = $this->_queryBuilder->insert("' . $tableName . '", array(';
		    $cpt = 0;

		    foreach($tableFields as $thisField) {
				if($thisField['label'] == "id")
				    continue;
				
				$source .= "'" . $thisField['label'] . "' => " . '$properties["' . $thisField['label'] . '"]';
				if($cpt < count($tableFields)-1)
				    $source .= ', ';
				$cpt++;
		    }
		    $source .= '))->getQuery();' . FileManager::getBackSpace();

		    $source .= FileManager::getTab(3) . 'try {' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(4) . 'return $this->_pdo->Query($query);' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(3) . '}' . FileManager::getBackSpace() . FileManager::getTab(3) . 'catch(PDOException $e){' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(4) . 'print $e->getMessage();' . FileManager::getBackSpace() . FileManager::getTab(3) . '}' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(3) . 'return array();' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(2) . '}' . FileManager::getBackSpace(2);

		    // Update
		    $source .= FileManager::getTab(2) . FileManager::getPrototype("update", array('id' => '_none_', 'properties' => '_none_')) . ' {' . FileManager::getBackSpace();
		    $source .= FileManager::getTab(3) . '$query = $this->_queryBuilder->update("' . $tableName . '", array(';
		    $cpt = 0;

		    foreach($tableFields as $thisField){
				if($thisField['label'] == "id")
				    continue;
				$source .= "'" . $thisField['label'] . "' => " . '$properties["' . $thisField['label'] . '"]';
				if($cpt < count($tableFields)-1)
				    $source .= ', ';
				$cpt++;
		    }
		    $source .= '))->where(array(array("link" => "", "left" => "id", "operator" => "=", "right" => $id )))->getQuery();' . FileManager::getBackSpace();
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
		
		/**
		 * getHeaderComment --> return a string which contains copyrights header comments
		 * @return string
		 */
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