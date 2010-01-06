<?php defined('SYSPATH') OR die('No direct access allowed.');

class Model_Hit_Stat extends HitLogger_ORM {

	protected $_belongs_to = array(
		'uri' => array( 'model' => 'hit_uri', 'foreign_key' => 'uri_id' ),
		'date' => array( 'model' => 'hit_date', 'foreign_key' => 'date_id' ),
	);

	protected function _initialize() {
		$this->_has_many['referers'] = array(
			'model' => 'hit_referer',
			'through' => HitLogger::instance()->config('tbl_prefix').'hit_referrals',
			'foreign_key' => 'stat_id',
			'far_key' => 'referer_id',
		);
		parent::_initialize();
	}



}
