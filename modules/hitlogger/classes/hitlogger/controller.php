<?php defined('SYSPATH') or die('No direct script access.');

class HitLogger_Controller extends Controller_Template {
	
	public $template = 'hitlogger/template';
	
	public function before() {
		parent::before();
		$cfg = HitLogger::instance()->config();
		$this->template->title = $cfg['page_title'];
		$this->template->filepaths = $cfg['filepaths'];
		$this->template->uri = array(
			'visitors' => url::base() . $this->request->route->uri(array('controller' => $this->request->controller, 'action' => '')),
			'pagehits' => url::base() . $this->request->route->uri(array('controller' => $this->request->controller, 'action' => 'pagehits')),
			'errorhits' => url::base() . $this->request->route->uri(array('controller' => $this->request->controller, 'action' => 'errorhits')),
			'referrals' => url::base() . $this->request->route->uri(array('controller' => $this->request->controller, 'action' => 'referrals')),
		);
		$this->template->content = '';
		if (Arr::get($cfg, 'enable_trails')) {
			$this->template->uri['trails'] = url::base() . $this->request->route->uri(array('controller' => $this->request->controller, 'action' => 'trails'));
		}
	}
	
	public function action_index() {
		// lists of months and years for the dropdowns
		list($months, $years, $first, $last) = $this->generate_months_years(ORM::factory('hit_visitor'), 'date');
		$month = Arr::get($_POST, 'month', sprintf('%d%02d', $last['year'], $last['month']));
		$year = Arr::get($_POST, 'year', $last['year']);

		// daily plot
		$visitors = ORM::factory('hit_visitor')
			->select(new Database_Expression('COUNT(*) AS total'))
			->with('date')
			->group_by('date_id')
			->order_by('date.datestamp', 'DESC')
			->where('date.datestamp', 'LIKE', "$month%")
			->where('incognito', '=', 0)
			->find_all()->as_array();
		$daily = $this->generate_data_series($visitors, 'DAILY', 'date');

		// monthly plot
		$visitors = ORM::factory('hit_visitor')
			->select(new Database_Expression('COUNT(*) AS total'))
			->with('date')
			->group_by('date.year', 'date.month')
			->order_by('date.year', 'DESC')
			->order_by('date.month', 'DESC')
			->where('date.year', '=', $year)
			->where('incognito', '=', 0)
			->find_all()->as_array();
		$monthly = $this->generate_data_series($visitors, 'MONTHLY', 'date');

		// finalize template
		$view_visitors = View::factory('hitlogger/visitors');
		$view_visitors->daily = $daily;
		$view_visitors->monthly = $monthly;
		$view_visitors->months = $months;
		$view_visitors->years = $years;
		$view_visitors->month = $month;
		$view_visitors->year = $year;
		$this->template->content = $view_visitors;
	}

	public function action_referrals() {
		// lists of months and years for the dropdowns
		$pref = HitLogger::instance()->config('tbl_prefix');
		list($months, $years, $first, $last) = $this->generate_months_years(ORM::factory('hit_referral'), 'stat:date');
		$month = Arr::get($_POST, 'month', sprintf('%d%02d', $last['year'], $last['month']));
		$year = Arr::get($_POST, 'year', $last['year']);

		// types of listing
		$listing_types = array(
			'd_u_r' => 'Date/Month - URI - Referer Domain',
			'd_r_u' => 'Date/Month - Referer Domain - URI',
			'u_d_r' => 'URI - Date/Month - Referer Domain',
			'u_r_d' => 'URI - Referer Domain - Date/Month',
			'r_d_u' => 'Referer Domain - Date/Month - URI',
			'r_u_d' => 'Referer Domain - URI - Date/Month',
		);
		$listing_type = Arr::get($_POST, 'listing_type', 'd_u_r');
		if (!in_array($listing_type, array_keys($listing_types))) {
			$listing_type = 'd_u_r';
		}

		$referrals = ORM::factory('hit_referral')
			->with('referer')
			->with('stat:date')
			->with('stat:uri');
		foreach (explode('_', $listing_type) as $d) {
			switch ($d) {
				case 'd': $referrals->order_by('stat:date.datestamp', 'DESC'); break;
				case 'u': $referrals->order_by('stat:uri.uri', 'ASC'); break;
				case 'r': $referrals->order_by('referer.domain', 'ASC'); break;
			}
		}
		$referrals->order_by('count', 'DESC')->order_by('referer.url', 'ASC');
		// daily

		$referral_daily = clone $referrals;
		$referral_daily = $referral_daily->where('stat:date.datestamp', 'LIKE', "$month%")
			->find_all()->as_array();

		$daily = array();
		foreach ($referral_daily as $referral) {
			list($k1, $k2, $k3) = self::generate_referral_keys($referral, $listing_type, 'DAILY');
			$daily[$k1][$k2][$k3][] = $referral->as_array();
		}

		// monthly
		$referral_monthly = clone $referrals;
		$referral_monthly = $referral_monthly->where('stat:date.year', '=', $year)
			->find_all()->as_array();

		$monthly = array();
		foreach ($referral_monthly as $referral) {
			list($k1, $k2, $k3) = self::generate_referral_keys($referral, $listing_type, 'MONTHLY');
			$ref = $referral->as_array();
			if (!isset($monthly[$k1][$k2][$k3][$ref['referer']['url']])) {
				$monthly[$k1][$k2][$k3][$ref['referer']['url']] = $ref;
			}
			else {
				$monthly[$k1][$k2][$k3][$ref['referer']['url']]['count'] += $ref['count'];
			}
		}

		$view_referrals = View::factory('hitlogger/referrals');
		$view_referrals->daily = $daily;
		$view_referrals->monthly = $monthly;
		$view_referrals->listing_types = $listing_types;
		$view_referrals->listing_type = $listing_type;
		$view_referrals->months = $months;
		$view_referrals->years = $years;
		$view_referrals->month = $month;
		$view_referrals->year = $year;
		$view_referrals->filepaths = $this->template->filepaths;
		$this->template->content = $view_referrals;
	}

	private static function generate_referral_keys($referral, $listing_type, $mode) {
		$keys = array();
		foreach (explode('_', $listing_type) as $d) {
			switch ($d) {
				case 'd': 
					if ($mode == 'DAILY') {
						$keys[] = sprintf('%d/%02d/%02d', $referral->stat->date->year, $referral->stat->date->month, $referral->stat->date->date);
					}
					else {
						$keys[] = sprintf('%d/%02d', $referral->stat->date->year, $referral->stat->date->month);
					}
					break;
				case 'u': $keys[] = '/' . $referral->stat->uri->uri; break;
				case 'r': $keys[] = $referral->referer->domain; break;
			}
		}
		return $keys;
	}

	public function action_pagehits() {
		// lists of months and years for the dropdowns
		list($months, $years, $first, $last) = $this->generate_months_years(ORM::factory('hit_stat')->where('status', 'BETWEEN', array(200, 299)), 'date');
		$month = Arr::get($_POST, 'month', sprintf('%d%02d', $last['year'], $last['month']));
		$year = Arr::get($_POST, 'year', $last['year']);

		// daily plot
		$stats = ORM::factory('hit_stat')
			->select(new Database_Expression('accesses AS total'))
			->with('date')
			->with('uri')
			->where('status', 'BETWEEN', array(200, 299))
			->where('accesses', '>', 0)
			->order_by('uri.uri', 'ASC')
			->order_by('date.datestamp', 'DESC')
			->where('date.datestamp', 'LIKE', "$month%")
			->find_all()->as_array();
		$stats2 = array();
		$dailys = array();
		$uri_max = HitLogger::instance()->config('uri_max_length');
		foreach ($stats as $stat) {
			$stat_uri = ($uri_max == 0? $stat->uri->uri : implode('/', array_slice(explode('/', $stat->uri->uri), 0, $uri_max)));
			$stats2[$stat_uri][] = $stat;
		}
		foreach ($stats2 as $uri => $stat) {
			$dailys["$uri"] = $this->generate_data_series($stat, 'DAILY', 'date');
		}

		// monthly plot
		$stats = ORM::factory('hit_stat')
			->select(new Database_Expression('SUM(accesses) AS total'))
			->with('date')
			->with('uri')
			->where('status', 'BETWEEN', array(200, 299))
			->where('accesses', '>', 0)
			->group_by('uri.uri', 'date.year', 'date.month')
			->order_by('uri.uri', 'ASC')
			->order_by('date.year', 'DESC')
			->order_by('date.month', 'DESC')
			->where('date.year', '=', $year)
			->find_all()->as_array();
		$stats2 = array();
		$monthlys = array();
		foreach ($stats as $stat) {
			$stat_uri = ($uri_max == 0? $stat->uri->uri : implode('/', array_slice(explode('/', $stat->uri->uri), 0, $uri_max)));
			$stats2[$stat_uri][] = $stat;
		}
		foreach ($stats2 as $uri => $stat) {
			$monthlys["$uri"] = $this->generate_data_series($stat, 'MONTHLY', 'date');
		}

		// finalize template
		$view_pagehits = View::factory('hitlogger/pagehits');
		$view_pagehits->dailys = $dailys;
		$view_pagehits->monthlys = $monthlys;
		$view_pagehits->months = $months;
		$view_pagehits->years = $years;
		$view_pagehits->month = $month;
		$view_pagehits->year = $year;
		$this->template->content = $view_pagehits;
	}

	public function action_errorhits() {
		// lists of months and years for the dropdowns
		list($months, $years, $first, $last) = $this->generate_months_years(ORM::factory('hit_stat')->where('status', '>=', 300), 'date');
		$month = Arr::get($_POST, 'month', sprintf('%d%02d', $last['year'], $last['month']));
		$year = Arr::get($_POST, 'year', $last['year']);

		// daily plot
		$stats = ORM::factory('hit_stat')
			->select(new Database_Expression('accesses AS total'))
			->with('date')
			->with('uri')
			->with('referers')
			->where('status', '>=', 300)
			->where('accesses', '>', 0)
			->order_by('date.datestamp', 'DESC')
			->order_by('uri.uri', 'ASC')
			->where('date.datestamp', 'LIKE', "$month%")
			->find_all()->as_array();
		$daily = array();
		foreach ($stats as $statobj) {
			$stat = $statobj->as_array();
			$stat['referers'] = array();
			foreach ($statobj->referers->find_all() as $referer) {
				if (!isset($stat['referers']["{$referer->id}"])) {
					$stat['referers']["{$referer->id}"] = $referer->as_array();
				}
			}
			$key = sprintf('%d/%02d/%02d', $stat['date']['year'], $stat['date']['month'], $stat['date']['date']);
			$daily[$key][] = $stat;
		}

		// monthly plot
		$stats = ORM::factory('hit_stat')
			->select(new Database_Expression('accesses AS total'))
			->with('date')
			->with('uri')
			->with('referers')
			->where('status', '>=', 300)
			->where('accesses', '>', 0)
			->order_by('date.datestamp', 'DESC')
			->order_by('uri.uri', 'ASC')
			->where('date.year', '=', $year)
			->find_all()->as_array();
		$monthly = array();
		foreach ($stats as $statobj) {
			$stat = $statobj->as_array();
			$stat['referers'] = array();
			foreach ($statobj->referers->find_all() as $referer) {
				if (!isset($stat['referers']["{$referer->id}"])) {
					$stat['referers']["{$referer->id}"] = $referer->as_array();
				}
			}
			$key1 = sprintf('%d/%02d', $stat['date']['year'], $stat['date']['month']);
			$key2 = $stat['status'] . $stat['uri']['uri'];
			if (!isset($monthly[$key1][$key2])) {
				$monthly[$key1][$key2] = $stat;
			}
			else {
				$monthly[$key1][$key2]['accesses'] += $stat['accesses'];
				$monthly[$key1][$key2]['referers'] = array_merge($monthly[$key1][$key2]['referers'], $stat['referers']);
			}
		}

		// finalize template
		$view_errorhits = View::factory('hitlogger/errorhits');
		$view_errorhits->daily = $daily;
		$view_errorhits->monthly = $monthly;
		$view_errorhits->months = $months;
		$view_errorhits->years = $years;
		$view_errorhits->month = $month;
		$view_errorhits->year = $year;
		$view_errorhits->filepaths = $this->template->filepaths;
		$this->template->content = $view_errorhits;
	}

	public function action_trails($session_id = '') {
		if (Arr::get($_POST, 'op') == 'truncate') {
			$trunc_date = explode('/', Arr::get($_POST, 'date', ''));
			if (count($trunc_date) == 3) {
				$datestamp = intval(implode('', $trunc_date));
				$dt = getdate();
				$today = intval(sprintf('%d%02d%02d', $dt['year'], $dt['mon'], $dt['mday']));
				if ($today - $datestamp > 0) {
					$trunc_trails = ORM::factory('hit_trail')
						->with('stat:date')
						->where('stat:date.datestamp', '<=', $datestamp)
						->find_all();
					foreach ($trunc_trails as $trail) {
						$trail->delete();
					}
					$this->request->redirect($this->request->uri);
				}
			}
		}

		$sessions_obj = ORM::factory('hit_trail')->with('session')->group_by('session_id')->order_by('session.timestamp', 'DESC')->find_all();
		$sessions = array();
		foreach ($sessions_obj as $s) {
			$s->session->with('visitor')->reload();
			$dt = date('Y/m/d', $s->session->timestamp);
			$sessions[$dt][] = $s->session->as_array();
		}

		$items = array();
		$session = ORM::factory('hit_session')->with('visitor')->where('session', '=', "$session_id")->find();
		if ($session->loaded()) {
			$walks = ORM::factory('hit_trail')
				->with('uri')->with('stat')
				->where('session_id', '=', $session->id)->where('previous', 'IS', NULL)->find_all();
			foreach ($walks as $walk) {
				$items[] = $walk->get_tree();
			}
		}

		// finalize template
		$view_trails = View::factory('hitlogger/trails');
		$view_trails->session_id = $session_id;
		$view_trails->items = $items;
		$view_trails->session = $session;
		$view_trails->sessions = $sessions;
		$view_trails->trail_uri = $this->template->uri['trails'];
		$view_trails->filepaths = $this->template->filepaths;
		$this->template->content = $view_trails;
	}

	protected function generate_months_years($model, $with = '') {
		$m1 = clone $model;
		if (!empty($with)) {
			$m1->with($with);
		}
		$objs = $m1->order_by('datestamp', 'ASC')->find_all();
		if (empty($objs)) {
			$cur = getdate();
			$month = sprintf('%d%02d', $cur['year'], $cur['mon']);
			$emptymonths = array(
				"$month" => sprintf('%d/%02d', $cur['year'], $cur['mon']),
			);
			$empty = array(
				'year' => $cur['year'],
				'month' => $cur['mon'],
			);
			return array($emptymonths, array( "{$cur['year']}" => $cur['year'] ), $empty, $empty);
		}
		$first_obj = $objs[0];
		$last_obj = $objs[count($objs) - 1];
		if (!empty($with)) {
			foreach (explode(':', $with) as $accessor) {
				$first_obj = $first_obj->$accessor;
				$last_obj = $last_obj->$accessor;
			}
		}
		$first = array('year' => $first_obj->year, 'month' => $first_obj->month);
		$last = array('year' => $last_obj->year, 'month' => $last_obj->month);
		$months = array();
		$years = array();
		$cur = $first;
		while ($cur['year'] < $last['year'] or $cur['month'] <= $last['month']) {
			if ($cur['month'] == 13) {
				$cur['month'] = 1;
				$cur['year']++;
			}
			if (!isset($years[$cur['year']])) {
				$years[$cur['year']] = $cur['year'];
			}
			$months[sprintf('%d%02d', $cur['year'], $cur['month'])] = sprintf('%d/%02d', $cur['year'], $cur['month']);
			$cur['month']++;
		}
		$months = array_reverse($months, TRUE);
		$years = array_reverse($years, TRUE);
		return array($months, $years, $first, $last);
	}

	protected function generate_data_series($model, $mode, $accessor = '', $firstdate = NULL) {
		switch ($mode) {

		case 'DAILY':
			$daily = array(
				'values' => array(),
				'max' => 0,
			);
			$timestamp = 0;
			$timestamp_b4 = 0;
			$firstdata = NULL;
			foreach (array_reverse($model) as $obj) {
				$objdate = (empty($accessor)? $obj : $obj->$accessor);
				$timestamp = gmmktime(0, 0, 0, $objdate->month, $objdate->date, $objdate->year) * 1000;
				if (empty($daily['values'])) {
					// reset $timestamp_b4 to the first day of the month
					$month_b4 = $objdate->month - 1;
					$year_b4 = $objdate->year;
					if ($month_b4 == 0) {
						$month_b4 = 12; $year_b4--;
					}
					$timestamp_b4 = gmmktime(0, 0, 0, $month_b4, cal_days_in_month(CAL_GREGORIAN, $month_b4, $year_b4), $year_b4) * 1000;
					$firstdata = $objdate;
				}
				while ($timestamp - $timestamp_b4 > 86400000) {
					// fill up days of the month that didn't have visitors to 0
					$timestamp_b4 += 86400000;
					$daily['values']["$timestamp_b4"] = array($timestamp_b4, 0);
				}
				if (isset($daily['values']["$timestamp"])) {
					$daily['values']["$timestamp"][1] += $obj->total;
				}
				else {
					$daily['values']["$timestamp"] = array($timestamp, $obj->total);
				}
				if ($daily['values']["$timestamp"][1] > $daily['max']) {
					$daily['max'] = $daily['values']["$timestamp"][1];
				}
				$timestamp_b4 = $timestamp;
				$month_b4 = $objdate->month;
				$year_b4 = $objdate->year;
			}
			if ($firstdata) {
				// fill up to the end of the month
				$timestamp_endofmonth = gmmktime(0, 0, 0, $firstdata->month, cal_days_in_month(CAL_GREGORIAN, $firstdata->month, $firstdata->year), $firstdata->year) * 1000;
				$cur = getdate();
				$ctime = gmmktime(0, 0, 0, $cur['mon'], $cur['mday'], $cur['year']) * 1000 - 86400000;
				while ($timestamp_endofmonth - $timestamp > 0 and $timestamp <= $ctime) {
					$timestamp += 86400000;
					$daily['values']["$timestamp"] = array($timestamp, 0);
				}
			}
			return $daily;

		default:
			$monthly = array(
				'values' => array(),
				'max' => 0,
			);
			$year_b4 = 0;
			$month_b4 = 0;
			$timestamp = 0;
			foreach (array_reverse($model) as $obj) {
				$objdate = (empty($accessor)? $obj : $obj->$accessor);
				if (empty($monthly['values'])) {
					// reset $month_b4 to the first month of the year
					$year_b4 = $objdate->year;
					$month_b4 = 0;
				}
				while ($year_b4 < $objdate->year or $month_b4 + 1 < $objdate->month) {
					$month_b4++;
					if ($month_b4 == 13) {
						$month_b4 = 1;
						$year_b4++;
					}
					$timestamp = gmmktime(0, 0, 0, $month_b4, 1, $year_b4) * 1000;
					$monthly['values']["$timestamp"] = array($timestamp, 0);
				}
				$timestamp = gmmktime(0, 0, 0, $objdate->month, 1, $objdate->year) * 1000;
				if (isset($monthly['values']["$timestamp"])) {
					$monthly['values']["$timestamp"][1] += $obj->total;
				}
				else {
					$monthly['values']["$timestamp"] = array($timestamp, $obj->total);
				}
				if ($monthly['values']["$timestamp"][1] > $monthly['max']) {
					$monthly['max'] = $monthly['values']["$timestamp"][1];
				}
				$year_b4 = $objdate->year;
				$month_b4 = $objdate->month;
			}
			if ($year_b4 > 0) {
				$ctime = time() * 1000 - 86400000;
				$thisyear = intval(date('Y'));
				$thismonth = intval(date('n'));
				while ($month_b4 < 12 and !($year_b4 == $thisyear and $month_b4 == $thismonth)) {
					$month_b4++;
					$timestamp = gmmktime(0, 0, 0, $month_b4, 1, $year_b4) * 1000 ;
					$monthly['values']["$timestamp"] = array($timestamp, 0);
				}
			}
			return $monthly;
		}
	}

}
