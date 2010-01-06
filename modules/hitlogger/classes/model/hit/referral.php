<?php defined('SYSPATH') OR die('No direct access allowed.');

class Model_Hit_Referral extends HitLogger_ORM {

	protected $_belongs_to = array(
		'stat' => array( 'model' => 'hit_stat', 'foreign_key' => 'stat_id' ),
		'referer' => array( 'model' => 'hit_referer', 'foreign_key' => 'referer_id' ),
	);

}
