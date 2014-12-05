<?php
    namespace fitzlucassen\DALGenerator\library;
	
    class FileManager {
		private static $_cpt_instance = 0;
		
		/**
		 * Constructor called only one time
		 */
		private function __construct() {
		}
		
		/**
		 * GetInstance -> return an instance of Config class only if there isn't any one else
		 * @return \Config|boolean
		 */
		public static function getInstance(){
		    if(FileManager::$_cpt_instance === 0){
				FileManager::$_cpt_instance++;
				return new FileManager();
		    }
		    else
				return false;
		}
		
		/**
		 * getTab
		 * @param type $nb
		 * @return string
		 */
		public static function getTab($nb = 1){
		    $source = "";
		    for($cpt = 0; $cpt < $nb; $cpt++)
				$source .= "\t";
		    return $source;
		}
		
		/**
		 * getBackSpace
		 * @param type $nb
		 * @return string
		 */
		public static function getBackSpace($nb = 1){
		    $source = "";
		    for($cpt = 0; $cpt < $nb; $cpt++)
				$source .= "\n";
		    return $source;
		}
		
		/**
		 * getPrototype
		 * @param type $name
		 * @param type $public
		 * @param type $static
		 * @return string
		 */
		public static function getPrototype($name, $params = array(), $public = true, $static = false){
		    $source = "";
		    
		    $source .= ($public ? "public " : "private ") . ($static ? "static " : "") . "function " . $name . "(";

		    $cpt = 0;
		    foreach ($params as $key => $value) {
		    	$source .= '$' . $key;
		    	if($value != '_none_')
		    		$source .= ' = ' . $value;

		    	if(count($params) > ($cpt + 1))
		    		$source .= ', ';
		    	
		    	$cpt++;
		    }

		    $source .= ')';
		    
		    return $source;
		}
		
		/**
		 * getComment
		 * @param type $nb_etoile
		 * @param type $open
		 * @return string
		 */
		public static function getComment($nb_etoile, $open = true){
		    $source = "";
		    if($open === true)
				$source .= "/";
		    else
				$source .= " ";
		    
		    for($cpt = 0; $cpt < $nb_etoile; $cpt++)
				$source .= "*";
		    
		    if($open === false)
				$source .= "/";
		    return $source;
		}

		public static function createDirectoryIfNotExist($path){
			if(!is_dir($path))
		    	mkdir($path);
		}

		public static function createFile($file, $type, $erase){
			if($erase)
				return fopen($file, $type);
			else {
				if(!file_exists($file))
					return fopen($file, $type);
				else
					return false;
			}
		}

		public static function closeFiles($array){
			foreach ($array as $value)
				if(isset($value) && gettype($value) !== 'boolean')
					fclose($value);
		}

		public static function writeInFiles($array){
			foreach ($array as $content => $file) 
				if(isset($file) && gettype($file) !== 'boolean')
					fwrite($file, $content);
		}
    }
