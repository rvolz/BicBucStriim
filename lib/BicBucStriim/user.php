<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2013 Rainer Volz
 * Licensed under MIT License, see LICENSE
 * 
 */ 

class Model_User extends RedBean_SimpleModel {

	public function to_json() {
		$props = self::getProperties();
		print "to_json";
		print_r($props);
		return json_encode($props);
	}
}

?>