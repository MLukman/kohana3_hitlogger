<?php defined('SYSPATH') OR die('No direct access allowed.');

class Model_Hit_Trail extends HitLogger_ORM {

	protected $_belongs_to = array(
		'previous' => array( 'model' => 'hit_trail', 'foreign_key' => 'previous' ),
		'uri' => array( 'model' => 'hit_uri', 'foreign_key' => 'uri_id' ),
		'session' => array( 'model' => 'hit_session', 'foreign_key' => 'session_id' ),
		'stat' => array( 'model' => 'hit_stat', 'foreign_key' => 'stat_id' ),
		'referer' => array( 'model' => 'hit_referer', 'foreign_key' => 'referer_id' ),
		'referer_uri' => array( 'model' => 'hit_uri', 'foreign_key' => 'referer_uri_id' ),
	);

	protected $_has_many = array(
		'next' => array( 'model' => 'hit_trail', 'foreign_key' => 'previous' ),
	);

	public function print_flow() {
		print '<div>' . $this->id . ':' . $this->uri . '</div>';
		foreach($this->next->find_all() as $next) {
			print '<div style="padding-left: 10px">';
			$next->print_flow();
			print '</div>';
		}
	}

	public function get_tree() {
		$children = array();
		$next_all = $this->next;
		foreach ($this->_related as $with => $val) {
			// make sure the children are loaded with same 'with()'
			$next_all = $next_all->with($with);
		}
		foreach($next_all->find_all() as $next) {
			$children[] = $next->get_tree();
		}
		return array(
			'item' => $this->as_array(),
			'children' => $children,
		);
	}
}
