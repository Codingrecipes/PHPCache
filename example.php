<?php
	
	require_once 'phpcache.class.php';
	
	/*
	$database = array(
		'type'  => 'sqlite',
		'name'  => 'PATH TO A DATABASE FILE',
		'table' => 'PHPCache'
	);
	*/
	
	$database = array(
		'type'  => 'mysql',
		'name'  => 'YOUR DATABASE NAME',
		'table' => 'PHPCache',
		'user'  => 'YOUR DATABASE USERNAME',
		'pass'  => 'YOUR DATABASE USERNAME\'S PASSWORD',
		'host'  => 'localhost' /* Or maybe IP address of your database server */
	);
	
	PHPCache::configure($database);
	$cache = PHPCache::instance();
	// $cache->create_table(); /* Call this the first time to create the table and then comment it out... */
	
	if( ($results = $cache->get('result_of_some_nasty_code')) !== false ) {
		$tpl->assign($results);
		/* Or maybe return $results depending on where you use this */
	} else {
		/***********************
		 * Your slow code here *
		 ***********************/
		 
		$cache->store('result_of_some_nasty_code', $results_of_your_slow_code, PHPCACHE_1_HOUR * 5); /* Cache for 5 hours */
	}
	
?>