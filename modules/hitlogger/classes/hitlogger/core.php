<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * HitLogger core class.
 *
 * @package    HitLogger
 * @author     Lukman
 * @copyright  (c) 2008-2009 Nusantara Software
 */
class HitLogger_Core {

	protected static $_instance = array();
	protected static $_active_instance = 'default';
	protected static $_configs;
	protected $_year;
	protected $_month;
	protected $_date;
	protected $_datestamp;
	protected $_timestamp;
	protected $_session_key;
	protected $_request;
	protected $_finalized = FALSE;
	protected $_hitsession = NULL;
	protected $_hituri = NULL;
	protected $_hitstat = NULL;
	protected $_hitreferral = NULL;
	protected $_hitrefuri = NULL;
	protected $_hittrail = NULL;
	protected $_translated_uri;

	/**
	 * Initialize variables and load configurations to be used by this object.
	 *
	 * @return  void
	 */
	protected function __construct() {
		// initialize variables
		$date = getdate();
		$this->_timestamp = $date[0];
		$this->_year = $date['year'];
		$this->_month = $date['mon'];
		$this->_date = $date['mday'];
		$this->_datestamp = intval(sprintf('%d%02d%02d', $this->_year, $this->_month, $this->_date));
	}

	/**
	 * Return configuration value if $key is supplied, otherwise return the
	 * whole configurations as array.
	 *
	 * @param   string  configuration key
	 * @return  mixed  the config value or the config array
	 */
	public static function config($key = '') {
		if (!self::$_configs) {
			// read configs
			self::$_configs = Kohana::config('hitlogger')->as_array();
		}
		return (!empty($key)? Arr::get(self::$_configs, $key) : self::$_configs);
	}

	/**
	 * Instantiate and/or return HitLogger instance.
	 * Optionally specify $id to get specific instance.
	 *
	 * @param   string  instance id
	 * @return  HitLogger_Core
	 */
	public static function instance($id = '') {
		if (empty($id)) {
			$id = self::$_active_instance;
		}
		if (!isset(self::$_instance["$id"])) {
			self::$_instance["$id"] = new self();
		}
		return self::$_instance["$id"];
	}

	/**
	 * Set the instance id that will be returned when calling ::instance()
	 * without the instance id.
	 *
	 * @param   string  instance id
	 * @return  void
	 */
	public static function set_active_instance($id) {
		if (isset(self::$_instance["$id"])) {
			self::$_active_instance = $id;
		}
	}

	/**
	 * The core function that needs to be called to log a hit
	 *
	 * @param   Kohana_Request  the request object with uri and status
	 * @return  $this
	 */
	public function record_hit(Kohana_Request &$request) {
		$this->_request = $request;

		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {  //check ip from share internet
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		}
		elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {  //check ip from proxy
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		// translate uri
		$this->_translated_uri = self::translate_uri($this->_request->uri);
		if (is_null($this->_translated_uri)) {
			return $this;
		}

		// create db tables if not exist
		$prefix = self::config('tbl_prefix');
		$dbprof = self::config('db_profile');
		$db = (empty($dbprof)? Database::instance() : Database::instance($dbprof));
		if (count($db->list_tables($prefix . 'hit_%')) == 0) {
			$schemas = array(
				'hit_dates',
				'hit_visitors',
				'hit_sessions',
				'hit_trails',
				'hit_uris',
				'hit_stats',
				'hit_referers',
				'hit_referrals',
			);
			foreach ($schemas as $schema) {
				$db->query(NULL, View::factory("schemas/$schema")->set('prefix', $prefix), FALSE);
			}
		}

		/* start logging */
			
		/* hit_date */
		$hitdate = ORM::factory('hit_date', array(
			'year' => $this->_year,
			'month' => $this->_month,
			'date' => $this->_date,
		));
		if (!$hitdate->loaded()) {
			$hitdate->values(array(
				'year' => $this->_year,
				'month' => $this->_month,
				'date' => $this->_date,
				'datestamp' => $this->_datestamp,
			))->save();

			// new day so clean up trails if necessary
			$trails_cleanup_age = self::config('trails_cleanup_age');
			if ($trails_cleanup_age > 0) {
				$expired = date('Ymd', mktime(0,0,0, $this->_month, $this->_date, $this->_year) - ($trails_cleanup_age * 86400));
				$old_trails = ORM::factory('hit_trail')->with('stat:date')->where('stat:date.datestamp', '<=', $expired)->find_all();
				foreach ($old_trails as $trail) {
					$trail->delete();
				}
			}
		}

		/* hit_visitor */
		$visitorinfo = array(
			'ip' => $ip,
			'date_id' => $hitdate,
		);
		$hitvisitor = ORM::factory('hit_visitor', $visitorinfo);
		if (!$hitvisitor->loaded()) {
			// ip you don't want logged
			foreach (self::config('incognito_ip') as $nolog) {
				$nolog = str_replace('.', '\.', $nolog);
				$nolog = str_replace('*', '(.*)', $nolog);
				$nolog = str_replace('?', '(.)', $nolog);
				if (preg_match('/^'. $nolog .'$/i', $ip) > 0) {
					$visitorinfo['incognito'] = TRUE;
				}
			}
			// hostnames you don't want logged
			// we do this here instead of at the beginning because we don't want
			// to repeat the delay that is caused by gethostbyaddr()
			try {
				$host = @gethostbyaddr($ip);
				$visitorinfo['hostname'] = $host;
				foreach (self::config('incognito_host') as $nolog) {
					$nolog = str_replace('.', '\.', $nolog);
					$nolog = str_replace('*', '(.*)', $nolog);
					$nolog = str_replace('?', '(.)', $nolog);
					if (preg_match('/^'. $nolog .'$/i', $host) > 0) {
						$visitorinfo['incognito'] = TRUE;
					}
				}
			}
			catch (Exception $e) {
				// do nothing to not interrupt the logging
			}
			
			$hitvisitor->values($visitorinfo);
			$hitvisitor->save();
		}

		if ($hitvisitor->incognito) {
			// stop logging incognito visitor (by ip or hostname)
			return $this;
		}

		// register finalize_hit() to be called at script shutdown
		register_shutdown_function(array($this, 'finalize_hit'));

		/* figure out referer */
		$referer_id = NULL;
		$referer_url = Arr::get($_SERVER, 'HTTP_REFERER');
		$urlbase = url::base(FALSE, TRUE);
		if (strpos($referer_url, $urlbase) !== FALSE) {
			$referer_uri = substr($referer_url, strlen($urlbase));
			$referer_uri = self::translate_uri($referer_uri);
			$this->_hitrefuri = ORM::factory('hit_uri', array( 'uri' => $referer_uri ));
		}

		/* hit_referer */
		if (!empty($referer_url) AND !$this->_hitrefuri) {
			$referer = ORM::factory('hit_referer', array('url' => $referer_url));
			if (!$referer->loaded()) {
				$referer->url = $referer_url;
				$referer->domain = parse_url($referer_url, PHP_URL_HOST);
				$referer->save();
			}
			$referer_id = $referer->id;
		}

		// read/setup session
		$this->_session_key = Session::instance()->get('hitlogger_session', NULL);
		if ($this->_session_key) {
			if (ORM::factory('hit_session')->where('session', '=', $this->_session_key)->where('visitor_id', '<>', $hitvisitor->id)->find()->loaded()) {
				// some other visitor uses the same session key so pick a new one
				$this->_session_key = NULL;
			}
		}
		if (!$this->_session_key) {
			$this->_session_key = Text::random('alnum', 8);
			Session::instance()->set('hitlogger_session', $this->_session_key);
		}

		/* hit_session */
		$sessioninfo = array(
			'visitor_id' => $hitvisitor->id,
			'session' => $this->_session_key,
			'user_agent' => $_SERVER['HTTP_USER_AGENT'],
		);
		$visited_uri = array();
		$this->_hitsession = ORM::factory('hit_session', $sessioninfo);
		if (!$this->_hitsession->loaded()) {
			$sessioninfo['timestamp'] = $this->_timestamp;
			$this->_hitsession->values($sessioninfo)->save();
		}
		elseif (self::config('enable_trails')) {
			// since we are using the trail logging, let's use this instead to get visited_uri
			$vis = ORM::factory('hit_trail')
				->with('uri')
				->with('stat')
				->where('session_id', '=', $this->_hitsession->id)
				->group_by('uri_id')
				->find_all();
			foreach ($vis as $v) {
				$status_uri = $v->stat->status.$v->uri->uri;
				$visited_uri[$status_uri] = $status_uri;
			}
		}
		else {
			$visited_uri = Kohana::cache('hitlogger_' . $this->_session_key, NULL, self::config('cache_timeout'));
			if (!$visited_uri) {
				$visited_uri = array();
			}
		}

		/* hit_uri */
		$uriinfo = array('uri' => $this->_translated_uri);
		$this->_hituri = ORM::factory('hit_uri', $uriinfo);
		$this->_hituri->values($uriinfo)->save();

		/* hit_stats */
		$visited = in_array($this->_request->status . $this->_translated_uri, $visited_uri);
		$statinfo = array(
			'uri_id' => $this->_hituri->id,
			'date_id' => $hitdate->id,
			'status' => 200,
		);
		$hitstat = ORM::factory('hit_stat', $statinfo);
		if (!$visited) {
			// increase hit count
			$this->_hituri->hits++;
			$this->_hituri->save();
			// modify hit stat
			$statinfo['accesses'] = ($hitstat->loaded()? $hitstat->accesses + 1:1);
			$hitstat->values($statinfo)->save();
			$this->_hitstat = $hitstat;

			/* hit_referrals */
			if ($referer_id > 0) {
				$hitreferral = ORM::factory('hit_referral', array(
					'stat_id' => $this->_hitstat->id,
					'referer_id' => $referer_id,
				));
				$hitreferral->values(array(
					'stat_id' => $hitstat->id,
					'referer_id' => $referer_id,
					'count' => $hitreferral->loaded()? $hitreferral->count + 1:1,
				));
				$hitreferral->save();
				$this->_hitreferral = $hitreferral;
			}
		}

		/* hit_trail */
		if (self::config('enable_trails')) {
			// check if this is a page refresh
			$lasthit = ORM::factory('hit_trail')
				->with('stat')
				->where('session_id', '=', $this->_hitsession->id)
				->order_by('id', 'DESC')->find();
			$this_referer_id = ($this->_hitreferral? $this->_hitreferral->referer_id : NULL);
			$this_referer_uri_id = ($this->_hitrefuri? $this->_hitrefuri->id : NULL);
			if (!$lasthit->loaded()
					OR $lasthit->uri_id != $this->_hituri->id
					OR $lasthit->referer_id != $this_referer_id
					OR (
						$lasthit->referer_uri_id != $this_referer_uri_id
						AND $lasthit->uri_id != $this_referer_uri_id
					)
					OR $lasthit->stat->status != $this->_request->status
			) {
				// yup, we are fine!
				$this->_hittrail = ORM::factory('hit_trail');
				$this->_hittrail->uri_id = $this->_hituri->id;
				$this->_hittrail->session_id = $this->_hitsession->id;
				$this->_hittrail->referer_id = $this_referer_id;
				$this->_hittrail->referer_uri_id = $this_referer_uri_id;
				$this->_hittrail->timestamp = $this->_timestamp;
				if ($hitstat->loaded()) {
					$this->_hittrail->stat_id = $hitstat->id;
				}
				// figure out previous hit
				if ($this_referer_uri_id != NULL) {
					$prevhit = ORM::factory('hit_trail')
						->where('uri_id', '=', $this_referer_uri_id)
						->where('session_id', '=', $this->_hittrail->session_id)
						->order_by('id', 'DESC')->find();
					if ($prevhit->loaded()) {
						$this->_hittrail->previous = $prevhit->id;
					}
				}
				$this->_hittrail->save();
			}
		}

		return $this;
	}

	/**
	 * Finalize the logging by updating the request status if necessary
	 *
	 * @return  void
	 */
	public function finalize_hit() {
		try {

			if ($this->_finalized or !$this->_hitstat) {
				return;
			}

			if (self::config('enable_trails')) {
				$visited_obj = ORM::factory('hit_trail')
					->where('stat_id', '=', $this->_hitstat->id)
					->where('session_id', '=', $this->_hitsession->id);
				if ($this->_hittrail) {
					$visited_obj->where('id', '<>', $this->_hittrail->id);
				}
				$visited = $visited_obj->find()->loaded();
			}
			else {
				// since we are not using the trail logging,
				// we have to depend on the cache system to keep track of visited_uri
				$cache_keys = Kohana::cache('hitlogger_session_caches', NULL, 86400);
				if (!empty($cache_keys)) {
					foreach ($cache_keys as $key) {
						if (Kohana::cache($key, NULL, self::config('cache_timeout')) == NULL) {
							unset ($cache_keys[$key]);
						}
					}
				}
				$cache_key = 'hitlogger_' . $this->_session_key;
				$visited_key = $this->_request->status . $this->_translated_uri;
				$visited_uri = Kohana::cache($cache_key, NULL, self::config('cache_timeout'));
				$visited = isset($visited_uri[$visited_key]);
				$visited_uri[$visited_key] = $visited_key;
				Kohana::cache($cache_key, $visited_uri);
				$cache_keys[$cache_key] = $cache_key;
				Kohana::cache('hitlogger_session_caches', $cache_keys);
			}

			if ($this->_hitstat->status == $this->_request->status) {
				return;
			}

			// just refresh from db in case it has been updated in between
			$this->_hitstat->reload();
			$this->_hitstat->accesses--;
			$this->_hitstat->save();

			// build or update new stat
			$nstatinfo = array(
				'uri_id' => $this->_hitstat->uri_id,
				'date_id' => $this->_hitstat->date_id,
				'status' => $this->_request->status,
			);
			$nstat = ORM::factory('hit_stat', $nstatinfo);
			if (!$visited) {
				$nstatinfo['accesses'] = ($nstat->loaded()? $nstat->accesses : 0) + 1;
			}
			$nstat->values($nstatinfo)->save();

			// fix the referral
			if ($this->_hitreferral) {
				$this->_hitreferral->stat_id = $nstat->id;
				$this->_hitreferral->save();
			}

			// fix the trail
			if ($this->_hittrail) {
				$lasthit = ORM::factory('hit_trail')
					->where('session_id', '=', $this->_hitsession->id)
					->where('id', '<>', $this->_hittrail->id)
					->order_by('id', 'DESC')->find();
				if ($lasthit->loaded()
						AND $lasthit->uri_id == $this->_hituri->id
						AND $lasthit->referer_id == ($this->_hitreferral? $this->_hitreferral->referer_id:NULL)
						AND (
							$lasthit->uri_id == ($this->_hitrefuri? $this->_hitrefuri->id:NULL)
							OR $lasthit->referer_uri_id == ($this->_hitrefuri? $this->_hitrefuri->id:NULL)
						)
						AND $lasthit->stat_id == $nstat->id
				) {
					// the previous trail item has absolutely the same uri, referer and status so delete this item
					$this->_hittrail->delete();
					$this->_hittrail = NULL;
				}
				else {
					$this->_hittrail->stat_id = $nstat->id;
					$this->_hittrail->save();
				}
			}

			$this->_finalized = TRUE;
		}
		catch (Exception $e) {
			Kohana::exception_handler($e);
		}
	}

	/**
	 * Get the number of hits for a specific uri and the year, month and date the counting started.
	 *
	 * @param   string  the uri
	 * @return  array  array( number of hits, since year, since month, since date )
	 */
	public static function get_hits($uri) {
		$ruri = self::translate_uri($uri);
		$f = ORM::factory('hit_uri')->with('since')->where('uri', '=', $ruri)->find();
		if ($f->loaded()) {
			return array($f->hits, $f->since->year, $f->since->month, $f->since->date);
		}
		else {
			return array(0,0,0,0);
		}
	}

	/**
	 * Translate uri based on the translation rules defined in config/hitlogger.php
	 *
	 * @param   string  input uri
	 * @return  string  translated uri
	 */
	public static function translate_uri($uri) {
		$translated_uri = $uri;
		foreach (self::config('translate_uri') as $src => $value) {
			$matches = array();
			if (preg_match('/^'. str_replace('/', '\/', $src) .'$/i', $translated_uri, $matches) > 0) {
				if (is_string($value)) {
					for ($i = 1; $i < count($matches); $i++) {
						$value = str_replace('{'.$i.'}', $matches[$i], $value);
					}
				}
				$translated_uri = $value;
			}
		}
		return $translated_uri;
	}

	/**
	 * Generate <img> tag for displaying hitmeter for a particular uri
	 *
	 * @param   string  the uri
	 * @return  string  <img> tag
	 */
	public static function get_hitmeter($uri) {
		return HTML::image(URL::base(TRUE, TRUE) .'hitmeter?u='.$uri);
	}
	
}