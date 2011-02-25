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
	
	
	if( !defined('PHPCACHE_BASE_DIR') )   			define('PHPCACHE_BASE_DIR', 			dirname(__FILE__));
	if( !defined('PHPCACHE_DRIVER_DIR') ) 			define('PHPCACHE_DRIVER_DIR', 		PHPCACHE_BASE_DIR .DIRECTORY_SEPARATOR .'drivers');
	if( !defined('PHPCACHE_BD_LOGIN_ATTEMPT') ) 	define('PHPCACHE_BD_LOGIN_ATTEMPT', 100);
	
	define('PHPCACHE_TIME_NOW', 			time());
	define('PHPCACHE_1_SECOND', 			1);
	define('PHPCACHE_1_MINUTE', 			PHPCACHE_1_SECOND * 60);
	define('PHPCACHE_1_HOUR', 				PHPCACHE_1_MINUTE * 60);
	define('PHPCACHE_1_DAY', 				PHPCACHE_1_HOUR   * 24);
	define('PHPCACHE_1_WEEK', 				PHPCACHE_1_DAY 	* 4);
	define('PHPCACHE_1_MONTH', 			PHPCACHE_1_DAY		* 30);
	define('PHPCACHE_1_YEAR', 				PHPCACHE_1_MONTH	* 12);
	
	/**
	 *	PHPCACHE_GC_PROBABILITY = 10 &
	 * PHPCACHE_GC_DIVISOR = 100
	 * Means there is 10% chance that the garbage collection will be done on each
	 * PHPCache::configure($database) call
	 */
	if( !defined('PHPCACHE_GC_PROBABILITY') ) define('PHPCACHE_GC_PROBABILITY',	1);
	if( !defined('PHPCACHE_GC_DIVISOR') )   	define('PHPCACHE_GC_DIVISOR',			100);
	
	/**
	 * TO = Table optimizer
	 */
	if( !defined('PHPCACHE_TO_PROBABILITY') ) define('PHPCACHE_TO_PROBABILITY',	10);
	if( !defined('PHPCACHE_TO_DIVISOR') )   	define('PHPCACHE_TO_DIVISOR',			100);
	
	require_once PHPCACHE_BASE_DIR   .DIRECTORY_SEPARATOR .'phpcache.interface.php';
	require_once PHPCACHE_DRIVER_DIR .DIRECTORY_SEPARATOR .'phpcache.driver.interface.php';
	
	
	class PHPCache implements PHPCache_Interface {
		
		private $db_handle;
		private $db_table;
		static  private $instance;
		private $time_now;
		private $last_error;
		
		
		private function __construct($params) {
			if( !isset($params['type']) ) {
				$this->trigger_error('I couldn\'t find the database type in $params[\'type\'].');
			}
			$database_type = strtolower($params['type']);
			
			$driver_file   = PHPCACHE_DRIVER_DIR .DIRECTORY_SEPARATOR .'phpcache.driver.' .$database_type .'.class.php';
			if( !file_exists($driver_file) ) {
				$this->trigger_error('PHPCache doesn\'t support the database type in $params[\'type\'] yet.');
			}	
			require_once($driver_file);
			
			$driver_class		 = 'PHPCache_Driver_' .$database_type;
			$this->db_handle = new $driver_class($params);
			
			$this->db_table   = isset($params['table']) ? $params['table'] : 'PHPCache';
			$this->time_now   = PHPCACHE_TIME_NOW;
			$this->last_error = NULL;
			
			$this->clean_up();
		}
		
		public static function configure($params) {
			if( self::$instance == NULL ) {
				self::$instance = new PHPCache($params);
			}
		}
		
		public static function instance() {
			if( self::$instance == NULL ) {
				$this->trigger_error('You have to call PHPCache::configure first.');
			}
			return self::$instance;
		}
		
		public function create_table() {
			$this->db_handle->create_table($this->db_table);
		}
		
		public function store($key, $value, $expires) {
			$data = array(
				'PHPCache_key' 	 => $this->db_handle->escape(md5($key)),
				'PHPCache_value'	 => $this->db_handle->escape(serialize($value)),
				'PHPCache_expires' => $this->db_handle->escape($this->time_now + $expires)
			);
			
			$query = "
					REPLACE INTO 
						{$this->db_table}
					   (
							PHPCache_key,
							PHPCache_value,
							PHPCache_expires
						)
					VALUES
						(
							 {$data['PHPCache_key']},
							 {$data['PHPCache_value']},
							 {$data['PHPCache_expires']}
						)
			";
			
			if( $this->db_handle->query($query) ) {
				return true;
			} else {
				$this->last_error = $this->db_handle->error();
				return false;
			}
		}
		
		public function get($key) {
			$key 	= $this->db_handle->escape(md5($key));
			
			$query = "
				SELECT 
					PHPCache_value, PHPCache_expires
				FROM 	 
					{$this->db_table}
				WHERE
					PHPCache_key = $key
			";
			
			if( !($result = $this->db_handle->query($query)) ) {
				$this->trigger_error($this->db_handle->error());
				return false;
			}
			
			if( $result->num_rows < 1 ) {
				return false;
			}
			
			$data	  = $result->fetch_assoc();
			
			if( $data['PHPCache_expires'] < $this->time_now ) {
				return false;
			}
			
			if( $data['PHPCache_value'] && trim($data['PHPCache_value']) != '' ) {
				$_data = unserialize($data['PHPCache_value']);
				if( $_data === false ) {
					$this->trigger_error("Unserialize failed, you might need to increase the size of database column {$this->db_table}.PHPCache_value");
					return false;
				}
				return $_data;
			} else {
				return NULL;
			}
		}
		
		public function set_expire($key) {
			$key 	 		= $this->db_handle->escape(md5($key));
			$expires 	= $this->db_handle->escape($this->time_now - PHPCACHE_1_YEAR);
			
			$query = "
				REPLACE INTO 
					{$this->db_table}
					(
						PHPCache_key,
						PHPCache_expires
					)
				VALUES
					(
						 {$key},
						 {$expires}
					)
			";
			$this->db_handle->query($query);
		}
		
		public function remove($key) {
			$key 	 = $this->db_handle->escape(md5($key));
			$query = "
				DELETE
				FROM
					{$this->db_table}
				WHERE
					PHPCache_key = $key
			";
			$this->db_handle->query($query);
		}
		
		public function clean_up() {
			if( rand(1, PHPCACHE_GC_DIVISOR) <= PHPCACHE_GC_PROBABILITY ) {
				$this->gc();
			}
			if( rand(1, PHPCACHE_TO_DIVISOR) <= PHPCACHE_TO_PROBABILITY ) {
				$this->optimize_table();
			}
		}
		
		public function gc() {
			$query = "
				DELETE
				FROM 	 
					{$this->db_table}
				WHERE
					PHPCache_expires < {$this->time_now}
			";
			$this->db_handle->query($query);
		}
		
		public function optimize_table() {
			$this->db_handle->optimize_table($this->db_table);
		}
		
		private function trigger_error($msg) {
			$this->last_error = $msg;
			trigger_error("PHPCache Error: $msg", E_USER_ERROR);
			exit;
		}
		
		public function has_error() {
			return $this->last_error != NULL;
		}
		
		public function last_error() {
			return $this->last_error;
		}
		
	} // Class
	
?>