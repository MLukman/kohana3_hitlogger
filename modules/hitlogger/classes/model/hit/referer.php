<?php defined('SYSPATH') OR die('No direct access allowed.');

class Model_Hit_Referer extends HitLogger_ORM {

	protected function _initialize() {
		$this->_has_many['stats'] = array(
			'model' => 'hit_stat',
			'through' => HitLogger::instance()->config('tbl_prefix').'hit_referrals',
			'foreign_key' => 'referer_id',
			'far_key' => 'stat_id',
		);
		parent::_initialize();
	}

}
