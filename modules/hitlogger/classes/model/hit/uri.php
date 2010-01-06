<?php defined('SYSPATH') OR die('No direct access allowed.');

class Model_Hit_Uri extends HitLogger_ORM {

	protected $_belongs_to = array(
		'since' => array( 'model' => 'hit_date', 'foreign_key' => 'since_date_id' ),
	);

	protected $_has_many = array(
		'stats' => array( 'model' => 'hit_stat', 'foreign_key' => 'uri_id' ),
	);

}
