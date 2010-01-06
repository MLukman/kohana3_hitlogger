<?php defined('SYSPATH') OR die('No direct access allowed.');

class Model_Hit_Visitor extends HitLogger_ORM {

	protected $_belongs_to = array(
		'date' => array( 'model' => 'hit_date', 'foreign_key' => 'date_id' ),
	);

}
