<?php

class Medoid_Admin {

	public function __construct() {
		$this->includes();
	}

	public function includes() {
		require_once dirname( __FILE__ ) . '/class-medoid-admin-menu.php';
	}
}

new Medoid_Admin();
