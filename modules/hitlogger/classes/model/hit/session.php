<?php defined('SYSPATH') OR die('No direct access allowed.');

class Model_Hit_Session extends HitLogger_ORM {

	protected $_belongs_to = array(
		'visitor' => array( 'model' => 'hit_visitor', 'foreign_key' => 'visitor_id' ),
	);
	
}
