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

	
	interface PHPCache_Interface {
		
		public static function configure($params);
		public static function instance();
		
		public function create_table();
		public function store($key, $value, $expires);
		public function get($key);
		public function set_expire($key);
		public function remove($key);
		public function clean_up();	
		public function gc();
		public function optimize_table();
		public function has_error();
		public function last_error();
		
	}
	
?>