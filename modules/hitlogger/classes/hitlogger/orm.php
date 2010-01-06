<?php defined('SYSPATH') OR die('No direct access allowed.');

class HitLogger_ORM extends ORM {

	protected function _initialize()
	{
		if (empty($this->_table_name))
		{
			// Table name is the same as the object name
			$this->_table_name = $this->_object_name;

			if ($this->_table_names_plural === TRUE)
			{
				// Make the table name plural
				$this->_table_name = inflector::plural($this->_table_name);
			}

			// prefix table
			$tbl_prefix = HitLogger::instance()->config('tbl_prefix');
			if (!empty($tbl_prefix) and strpos($this->_table_name, $tbl_prefix) == 0) {
				$this->_table_name = $tbl_prefix . $this->_table_name;
			}
		}
		// set the db profile
		$db_profile = HitLogger::instance()->config('db_profile');
		if (!empty($db_profile)) {
			$this->_db = $db_profile;
		}
		parent::_initialize();
	}

}