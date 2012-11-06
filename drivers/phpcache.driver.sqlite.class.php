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

	
	class PHPCache_Driver_sqlite implements PHPCache_Driver_Interface {
	
		private $database_file;
		private $db_handle;
		private $error_message;
		
		
		public function __construct($params) {
			$this->database_file = $params['name'];
			
			$login_attempt = 0;			
			do {
				$this->db_handle = @sqlite_open($this->database_file, 0666, $this->error_message);
			} while( !$this->db_handle && $login_attempt++ < PHPCACHE_BD_LOGIN_ATTEMPT );
			
			if( !$this->db_handle ) {
				return false;
			}
		}
		
		public function query($query) {
			
			$result = sqlite_query($this->db_handle, $query);
			if( !$result ) {
				return false;
			} 
			
			$num_rows  		= @sqlite_num_rows($result);
			$insert_id 		= @sqlite_last_insert_rowid($this->db_handle);
			$affected_rows = @sqlite_changes($this->db_handle);
					
			$result_set = new PHPCache_Driver_sqlite_Results($this->db_handle, $result, $num_rows, $insert_id, $affected_rows);
			return $result_set;
			
		}
		
		public function escape($value) {			
			$return = false;
			if( is_numeric($value) ) {
				$return = $value;
			} else {
				$return = '\'' .sqlite_escape_string($value) .'\'';
			}			
			return $return;
		}
		
		public function close() {
			sqlite_close($this->db_handle);
		}
		
		public function error() {
			return sqlite_error_string(sqlite_last_error($this->db_handle));
		}
		
		public function create_table($table_name) {
			$this->query("
				CREATE TABLE $table_name
				(
					PHPCache_key VARCHAR(41) PRIMARY KEY,
					PHPCache_value TEXT,
					PHPCache_expires INTEGER
				)
			");
			$this->query("
				CREATE INDEX 
					PHPCache_PHPCache_expires
				ON 
					".$table_name." (PHPCache_expires)
			");
		}
		
		public function optimize_table($table_name) {
			return true;
		}
		
	} // Class
	
	
	class PHPCache_Driver_sqlite_Results implements PHPCache_Driver_Results_Interface {
	
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
			return sqlite_fetch_array($this->result);		
		}
		
		public function fetch_array() {			
			return sqlite_fetch_array($this->result, SQLITE_NUM);		
		}
		
		public function fetch_assoc() {			
			return sqlite_fetch_array($this->result, SQLITE_ASSOC);			
		}
		
	} // Class
	
?>