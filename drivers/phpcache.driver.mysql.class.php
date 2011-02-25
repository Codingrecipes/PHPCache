<?php
	
	/**
	 * This program is free software; you can redistribute it and/or modify
    * it under the terms of the GNU General Public License as published by
    * the Free Software Foundation; either version 2 of the License, or
    * (at your option) any later version.
    * 
    * This program is distributed in the hope that it will be useful,
    * but WITHOUT ANY WARRANTY; without even the implied warranty of
    * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    * GNU General Public License for more details.
	 * 
    * You should have received a copy of the GNU General Public License
    * along with this program; if not, write to the Free Software
    * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
	 *
 	 * D O  N O T  R E M O V E  T H E S E  C O M M E N T S
	 *	 
	 * @Package PHPCache
	 * @Author  Hamid Alipour, http://blog.code-head.com/ http://www.hamidof.com/
	 */

	
	class PHPCache_Driver_mysql implements PHPCache_Driver_Interface {
	
		private $host;
		private $user;
		private $pass;
		private $db;
		private $db_handle;
		
		
		public function __construct($params) {		
			$this->host = $params['host'];
			$this->user = $params['user'];
			$this->pass = $params['pass'];
			$this->db   = $params['name'];
			
			$login_attempt = 0;
			do {
				$this->db_handle = mysql_connect($this->host, $this->user, $this->pass);
			} while( !$this->db_handle && $login_attempt++ < PHPCACHE_BD_LOGIN_ATTEMPT );
			
			if( !$this->db_handle ) {
				return false;
			}
			
			if( !mysql_select_db($this->db, $this->db_handle) ) {
				return false;
			}
		}
		
		public function query($query) {
			
			$result = mysql_query($query, $this->db_handle);
			if(!$result) {
				return false;
			} 
			
			$num_rows  		= @mysql_num_rows($result);
			$insert_id 		= @mysql_insert_id($this->db_handle);
			$affected_rows = @mysql_affected_rows($this->db_handle);
					
			$result_set = new PHPCache_Driver_mysql_Results($this->db_handle, $result, $num_rows, $insert_id, $affected_rows);
			return $result_set;
			
		}
		
		public function escape($value) {			
			$return = false;
			if( is_numeric($value) ) {
				$return = $value;
			} else {
				$return = '"' .mysql_real_escape_string($value, $this->db_handle) .'"';
			}			
			return $return;
		}
		
		public function close() {
			mysql_close($this->db_handle);
		}
		
		public function error() {
			return mysql_error();
		}
		
		public function create_table($table_name) {
			$this->query("
				CREATE TABLE $table_name 
				(
					`PHPCache_key` VARCHAR( 41 ) NOT NULL ,
					`PHPCache_value` TEXT NOT NULL ,
					`PHPCache_expires` INT( 11 ) UNSIGNED NOT NULL ,
					PRIMARY KEY ( `PHPCache_key` ) ,
					INDEX ( `PHPCache_expires` ) 
				)
			");
		}
		
		public function optimize_table($table_name) {
			$query 	= "SHOW TABLE STATUS";
			$result 	= $this->query($query);
			while($row = $result->fetch_assoc()) {
				if( $row['Name'] == $table_name ) {
					if( $row['Data_free'] > 0 ) {
						$query = "
							OPTIMIZE TABLE {$table_name}
						";
						$this->query($query);
					}
					break;
				}
			}
			return true;
		}
		
	} // Class
	
	
	class PHPCache_Driver_mysql_Results implements PHPCache_Driver_Results_Interface {
	
		private $db_handle;
		private $result;
		public $num_rows;
		public $insert_id;
		public $affected_rows;
		
		
		public function __construct(&$db_handle, $result, $num_rows, $insert_id, $affected_rows) {			
			$this->db_handle = &$db_handle;
			$this->result 	 = $result;
			$this->num_rows  = $num_rows;
			$this->insert_id = $insert_id;
			$this->affected_rows = $affected_rows;			
		}
		
		public function fetch_row() {			
			return mysql_fetch_row($this->result);		
		}
		
		public function fetch_array() {			
			return mysql_fetch_array($this->result);		
		}
		
		public function fetch_assoc() {			
			return mysql_fetch_assoc($this->result);			
		}
		
	} // Class
	
?>