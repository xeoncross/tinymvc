<?php


class welcome extends controller {
	function index() {
		print 'Hello from line'. __LINE__ . ' in file '. __FILE__;
	}
	
	function example() {
		$this->view('welcome');
		print 'Rendered in '. round(microtime(true) - START_TIME, 5). ' seconds';
	}
}