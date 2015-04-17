<?php

class oracleDB
{
    private $connection, $affectedRows, $parseStatement, $myLob, $lastparsesql;
    
    public $err;
  	
  
    function __construct($user,$pwd,$db){
  	$this->connection = oci_pconnect($user, $pwd, $db, "AL32UTF8");
      if ( ! $this->connection ) {
        echo "Unable to connect: " . var_dump( OCIError() );
        die();
      }
   
  } //function
    
  
    function parse($sql){
    	global $lastparsesql;
    	if ($lastparsesql == $sql){
    		return true;
    	}
    	else{
  	    $operation = strtolower(substr(ltrim($sql), 0, 6));
  			//update and delete must have where clause
  			if($operation == "update" || $operation == "delete"){
  				if(!stripos($sql, "where")){
  					$this->err = "Update or delete query with no where clause!";
  					return false;
  				}
  			}
  	    $this->parseStatement = oci_parse($this->connection, $sql);
  	    if ($this->parseStatement){
  	      $lastparsesql = $sql;
  	      return true;
  	    }
  	    else{
  	      return false;
  	    }
    	}
    }//parse
    
    
  
    function bind($bind,$value){
      oci_bind_by_name($this->parseStatement, $bind, $value);
    }//bind
    
    
  
    function bindBlobWithExecute($locator,$value){
      // Creates an "empty" OCI-Lob object to bind to the locator
  	$this->myLOB = oci_new_descriptor($this->connection, OCI_D_LOB);
  	// Bind the returned Oracle LOB locator to the PHP LOB object
  	oci_bind_by_name($this->parseStatement, $locator, $this->myLOB, -1, OCI_B_BLOB);
  	oci_execute($this->parseStatement, OCI_DEFAULT);
  	$this->myLOB->save($value);
    }//bind
    
    
    
    
    function bindQuery($expectone=''){
      if ($this->parseStatement){
        $result = oci_execute($this->parseStatement, OCI_COMMIT_ON_SUCCESS);
          if (!$expectone){
            $rows = array();
  		      while ($row = oci_fetch_object($this->parseStatement, OCI_ASSOC + OCI_RETURN_NULLS)) {
  			      $rows[] = $row;
  		      }
            return $rows;
          }
          else {
            return oci_fetch_object($this->parseStatement, OCI_ASSOC + OCI_RETURN_NULLS);
          }
        }
      else {
        return false;
      }
      oci_free_statement($this->parseStatement);
    }
    
    
  
    function bindExecute($commit=0){
      $this->affectedRows = 0;
      $result = oci_execute($this->parseStatement, OCI_DEFAULT);
      $this->affectedRows = oci_num_rows($this->parseStatement);
      if ($commit == 1){
        OCICommit($this->connection);
      }
      return $result;
    }
  
    
    function query($sql,$expectone=''){
  
  		$operation = strtolower(substr(ltrim($sql), 0, 6));
  
  		if($operation == "insert" || $operation == "update" || $operation == "delete"){
  			return false;
  		}
  
      $s = OCIParse($this->connection, $sql);
      if ($s){
        $result = OCIExecute($s, OCI_COMMIT_ON_SUCCESS);
          if (!$expectone){
            $rows = array();
  		      while ($row = oci_fetch_object($s, OCI_ASSOC + OCI_RETURN_NULLS)) {
  			      $rows[] = $row;
  		      }
            return $rows;
          }
          else {
            return oci_fetch_object($s, OCI_ASSOC + OCI_RETURN_NULLS);
          }
        }
      else {
        return false;
      }
      oci_free_statement( $s );
  
    }
  
  
    function execute($sql,$commit=''){
      $this->affectedRows = 0;
      $operation = strtolower(substr(ltrim($sql), 0, 6));
  		//update and delete must have where clause
  		if($operation == "update" || $operation == "delete"){
  			if(!stripos($sql, "where")){
  				$this->err = "Update or delete query with no where clause!";
  				return false;
  			}
  		}
      $s = OCIParse($this->connection, $sql);
      $result = OCIExecute($s, OCI_DEFAULT);
      $this->affectedRows = oci_num_rows($s);
      if ($commit == 1){
        OCICommit($this->connection);
      }
      oci_free_statement($s);
      return $result;
    }
  
  
    function commit(){
      OCICommit($this->connection);
    }
  
    function getAffectedRows(){
      return $this->affectedRows;
    }
  
    function db_close(){
      oci_close($this->connection);
    }
  
    function __destruct() {
      if (isset($this->parseStatement)) {
        oci_free_statement($this->parseStatement);
      }
      $this->db_close();
    }
    
    function rollback(){
      OCIRollback($this->connection);
    }

} 
?>
