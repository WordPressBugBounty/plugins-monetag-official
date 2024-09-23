<?php

class Ads_Anti_Adblock_Client
{
	const ROUTE_PUBLISHER_ZONES = '/v3/getPublisherZones';
	const ROUTE_SERVICE_WORKER = '/v3/getServiceWorker';
	const ROUTE_ANTI_ADBLOCK_TAG = '/v3/getTag';
	const ROUTE_CREATE_ZONE = '/v3/createPublisherZone';

	const TAG_DOMAIN = 'http://go.transferzenad.com';

	/**
	 * Settings helper instance
	 *
	 * @var Ads_Settings_Helper
	 */
	private $settings_helper;

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Site hostname
	 *
	 * @var string
	 */
	private $hostname;

	public function __construct($plugin_name, $version)
	{
		$this->settings_helper = new Ads_Settings_Helper($plugin_name);
		$this->version = $version;

		if (defined('MONETAG_HOSTNAME')) {
			$this->hostname = MONETAG_HOSTNAME;
		} else {
			$this->hostname = parse_url(get_site_url(), PHP_URL_HOST);
		}
	}

	/**
	 * Get all publisher zones by token
	 *
	 * @return array|null
	 */
	public function get_publisher_zones()
	{
		$zones = $this->get_request(
			$this->create_url(self::ROUTE_PUBLISHER_ZONES),
			true
		);

		if (!$zones) {
			return null;
		}

		return isset($zones[$this->hostname]) ? $zones[$this->hostname] : [];
	}

	public function create_url($endpoint, $params = array())
	{
		$params['token'] = $this->settings_helper->get_anti_adblock_token();

		if (!$params['token']) {
			return null;
		}

		return self::TAG_DOMAIN . $endpoint . '?' . http_build_query($params);
	}

	protected function process_response($response, $decode = false)
	{
		if (is_array($response)) {
			if ($decode) {
				$decodedData = json_decode($response['body'], true);

				if (json_last_error() === JSON_ERROR_NONE) {
					return $decodedData;
				}

				return null;
			}

			return $response['body'];
		}

		return null;
	}

	public function get_request($url, $decode = false, $data = [])
	{
		if ($url === null) {
			return null;
		}

		$args = array(
			'headers' => array(
				'user-agent' => $this->get_client_user_agent(),
			),
		);

		return $this->process_response(wp_remote_get($url, $args), $decode);
	}

	public function post_request($url, $decode = false, $data = [])
	{
		if ($url === null) {
			return null;
		}

		$args = array(
			'headers' => array(
				'user-agent' => $this->get_client_user_agent(),
				'content-type' => 'application/json; charset=utf-8'
			),
			'method' => 'POST',
			'body' => json_encode($data),
			'data_format' => 'body',
		);

		return $this->process_response(wp_remote_post($url, $args), $decode);
	}

	/**
	 * Get all publisher zones by token
	 *
	 * @return array|null
	 */
	public function create_publisher_zone($token, $publisherSiteId, $data)
	{
		return $this->post_request(
			$this->create_url(self::ROUTE_CREATE_ZONE, ['token' => $token, 'publisherSiteId' => $publisherSiteId]),
			true,
			$data
		);
	}

	/**
	 * @return string
	 */
	private function get_client_user_agent()
	{
		return 'WordPress/' . get_bloginfo('version') . ';Monetag/' . $this->version . '; ' . home_url();
	}
}
