<?php defined('SYSPATH') OR die('No direct access allowed.');

class Model_Hit_Date extends HitLogger_ORM {
	
	protected $_has_many = array(
		'stats' => array( 'model' => 'hit_stat', 'foreign_key' => 'date_id' ),
		'visitors' => array( 'model' => 'hit_visitor', 'foreign_key' => 'date_id' ),
	);
}
