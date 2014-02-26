<?php
    namespace fitzlucassen\DALGenerator\library;
    
    use PDO;

    /*
      Class : SQL
      Description : allow you to manage your SQL queries
     */
    class Sql {
	private $_db = '';			    // database
	private $_host = '';			    // host of the database
	private $_user = '';			    // user name
	private $_pwd = '';			    // password
	private $_con = '';			    // PDO connection
	private $_email = 'contact@passanger.fr';   // email of administrator
	
	/**
	 * Constructor
	 * @param string $db database
	 * @param string $host host of the database
	 * @param string $user user name
	 * @param string $pwd password
	 * @return PDOConnection
	 */
	public function __construct($db, $host, $user, $pwd) {
	    try  
	    { 
		$this->_db = $db;
		$this->_host = $host;
		$this->_user = $user;
		$this->_pwd = $pwd;

		$this->_con = new \PDO($this->GetDns(), $this->_user, $this->_pwd, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));

		// pour mysql on active le cache de requ�te 
		if($this->_con->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql') 
		    $this->_con->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true); 

		return $this->_con; 
	    } 
	    catch(PDOException $e) { 
		//On indique par email qu'on n'a plus de connection disponible 
		error_log(date('D/m/y') . ' à ' . date("H:i:s") . ' : ' . $e->getMessage(), 1, $this->_email); 
		$message = new Message(); 
		$message->outPut('Erreur 500', 'Serveur de BDD indisponible, nous nous excusons de la gêne occasionnée'); 
	    } 
	}
	
	/**
	 * GetDns -> get the DNS string for connection
	 * @return string host for connection
	 */
	public function GetDns() { 
	    return 'mysql:host=' . $this->_host . ';dbname=' . $this->_db; 
	} 
	
	/**
	 * Select -> execute a one-result query like GetById
	 * @param string $reqSelect your request
	 * @return array the result array
	 */
	public function Select($reqSelect) { 
	    try 
	    { 
		$this->_con->beginTransaction();
		$result = $this->_con->prepare($reqSelect); 
		$result->execute(); 
		$this->_con->commit();
		
		return $result->fetch(PDO::FETCH_ASSOC); 
	    } 
	    catch (Exception $e)  
	    { 
		//On indique par email que la requ�te n'a pas fonctionné. 
		error_log(date('D/m/y').' à '.date("H:i:s").' : '.$e->getMessage(), 1, $this->_email); 
		$this->_con->rollBack(); 
		$message = new Message(); 
		$message->outPut('Erreur dans la requêtte', 'Votre requête a été abandonné'); 
	    } 
	}
	
	/**
	 * SelectTable -> execute a multi-result query like GetAll
	 * @param string $reqSelect your request
	 * @return array the result array
	 */
	public function SelectTable($reqSelect) {
	    $this->_con->beginTransaction();
	    $result = $this->_con->prepare($reqSelect); 
	    $result->execute();
	    $this->_con->commit();
	    
	    /* R�cup�ration de toutes les lignes d'un jeu de r�sultats "�quivalent à mysql_num_row() " */ 
	    $resultat = $result->fetchAll(); 
	    return $resultat; 
	}
	/**
	 * Query -> Execute a simple query like "add" or "update"
	 * @param string $query your request
	 * @return array the result array
	 */
	public function Query($query) {
	    return $this->_con->query($query); 
	} 
	
	/**
	 * Exception_handler -> Manage exceptions
	 * @param type $exception
	 */
	public static function Exception_handler($exception) {
            // Output the exception details
            die('Uncaught exception: ' . $exception->getMessage());
        }
	
	/***********
	 * GETTERS *
	 ***********/
	
	/**
	 * GetConnection -> get the PDOConnection
	 * @return PDOConnection
	 */
	public function GetConnection() {
	    return $this->_con;
	}
	/**
	 * GetDB -> Get the database string
	 * @return string database
	 */
	public function GetDB(){
	    return $this->_db;
	}
    }
