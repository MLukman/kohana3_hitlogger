<?php defined('SYSPATH') or die('No direct script access.');

class HitLogger_Request extends Kohana_Request {

	protected $_loghit = FALSE;

	public static function instance(&$uri = TRUE)
	{
		try {
			$req = parent::instance($uri);
			if (!Kohana::$is_cli) {
				$req->_loghit = TRUE;
			}
			return $req;
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	public function execute() {
		$hitlogger = HitLogger::instance();
		if ($hitlogger->config('enabled') AND isset($this->_loghit) AND $this->_loghit) {
			$exception = NULL;
			$hitlogger->record_hit($this);
		}

		try {
			parent::execute();
		}
		catch (Exception $e) {
			throw $e;
		}

		return $this;
	}
	
}
