<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CodeIgniter Analytics Class
 *
 * Utility class for google analytics API
 * interaction. https://github.com/pierlo-upitup/google_analytics
 *
 * @package 	CodeIgniter
 * @subpackage 	Analytics
 * @category 	Library
 * @author 		Luis Felipe Pérez
 * @version 	0.1.2
 */

set_include_path(get_include_path() . PATH_SEPARATOR . APPPATH . 'third_party');
require_once(APPPATH . 'third_party/Google/Client.php');
require_once(APPPATH . 'third_party/Google/Service/Analytics.php');

class Analytics {

	private $account_mail;
	private $key_location;
	private $profile_id;
	private $service;
	private $start_date;
	private $end_date;
	private $ci;

	public function __construct($params = array())
	{
		$this->ci =& get_instance();
		$this->initialize($params);
		$this->set_credentials();
	}

	public function initialize($params = array())
	{
		$this->ci->load->config('analytics', TRUE);
		$this->key_location = $this->ci->config->item('key_location', 'analytics');
		$this->account_mail = $this->ci->config->item('account_mail', 'analytics');
		$this->profile_id   = $this->ci->config->item('profile_id', 'analytics');

		foreach ($params as $key => $value) {
			if (isset($this->$key) && !empty($value) && $key != 'ci') {
				$this->$key = $value;
			}
		}

		if (empty($this->key_location) || !file_exists($this->key_location)) {
			show_error('Invalid Google service key file location');
		}

		$this->start_date = date('Y-m-d', strtotime('-15 days'));
		$this->end_date   = date('Y-m-d');
	}

	private function set_credentials()
	{
		$client = new Google_Client();
		$client->setApplicationName("CodeigniterAnalytics");
		$service = new Google_Service_Analytics($client);

		if (isset($_SESSION['service_token'])) {
			$client->setAccessToken($_SESSION['service_token']);
		}

		$key = file_get_contents($this->key_location);
		$cred = new Google_Auth_AssertionCredentials(
		    $this->account_mail,
		    array(Google_Service_Analytics::ANALYTICS_READONLY),
		    $key
		);

		$client->setAssertionCredentials($cred);

		if($client->getAuth()->isAccessTokenExpired()) {
		  $client->getAuth()->refreshTokenWithAssertion($cred);
		}

		$_SESSION['service_token'] = $client->getAccessToken();
		$this->service = new Google_Service_Analytics($client);
	}

	public function set_date_range($start, $end)
	{
		$this->start_date = $start;
		$this->end_date   = $end;
	}

	public function get_data($metrics, $params)
	{
		$results    = $this->service->data_ga->get('ga:'.$this->profile_id, $this->start_date, $this->end_date, $metrics, $params);
		return $results->rows;
	}

	public function get_visits()
	{
		$metrics = "ga:users, ga:newUsers";
		$params  = array("dimensions" => "ga:date");

		return $this->get_data($metrics, $params);
	}

	public function get_page_views()
	{
		$metrics = "ga:pageviews";
		$params  = array("dimensions" => "ga:date");

		return $this->get_data($metrics, $params);
	}

	public function get_time()
	{
		$metrics = "ga:timeOnsite";
		$params  = array("dimensions" => "ga:date");

		return $this->get_data($metrics, $params);
	}

	public function get_browsers()
	{
		$metrics = "ga:users";
		$params  = array("dimensions" => "ga:browser,ga:browserVersion");

		return $this->get_data($metrics, $params);
	}

}
