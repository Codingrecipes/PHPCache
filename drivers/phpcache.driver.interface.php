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

	
	interface PHPCache_Driver_Interface {
	
		public function query($query);
		public function escape($value);
		public function close();
		public function error();
		public function create_table($table_name);
		public function optimize_table($table_name);
		
	}
		
	
	interface PHPCache_Driver_Results_Interface {
		
		public function fetch_row();
		public function fetch_array();
		public function fetch_assoc();
		
	}
	
?>