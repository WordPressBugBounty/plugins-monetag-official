<?php

class Monetag_Uninstall
{
	/**
	 * Settings helper instance
	 *
	 * @var Ads_Settings_Helper
	 */
	private $settings_helper;

	/**
	 * Zone helper instance
	 *
	 * @var Ads_Zone_Helper
	 */
	private $zone_helper;

	/**
	 * AntiAdBlock service instance
	 *
	 * @var Ads_Anti_Adblock
	 */
	private $aab_service;

	public function __construct()
	{
		$this->load_dependencies();

		$this->settings_helper = new Ads_Settings_Helper(Monetag_Meta::NAME);
		$this->zone_helper = new Ads_Zone_Helper(Monetag_Meta::NAME, Monetag_Meta::VERSION);
		$this->aab_service = new Ads_Anti_Adblock(Monetag_Meta::NAME, Monetag_Meta::VERSION);
	}

	public function run()
	{
		$zones = $this->settings_helper->get_zones_directions();

		$this->settings_helper->clear_plugin_options();
		$this->settings_helper->clear_zone_settings();

		$this->aab_service->clear_zone_tags_cache($zones);

		$this->zone_helper->clear_plugin_options();
		$this->zone_helper->clear_legacy_options();
	}

	private function load_dependencies()
	{
		require_once plugin_dir_path(__DIR__) . 'includes/class-monetag-meta.php';
		require_once plugin_dir_path(__DIR__) . 'includes/class-ads-settings-helper.php';
		require_once plugin_dir_path(__DIR__) . 'includes/class-ads-anti-adblock.php';
		require_once plugin_dir_path(__DIR__) . 'includes/class-ads-anti-adblock-client.php';
		require_once plugin_dir_path(__DIR__) . 'includes/class-ads-zone-helper.php';
		require_once plugin_dir_path(__DIR__) . 'includes/class-ads-options.php';
	}
}
