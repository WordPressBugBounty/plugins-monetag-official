<?php

/**
 * The public-facing functionality of the plugin.
 */
class Ads_Public
{
	/**
	 * Settings helper instance
	 *
	 * @var Ads_Settings_Helper
	 */
	private $setting_helper;

	/**
	 * Tag cache service instance
	 *
	 * @var Ads_Tag_Cache
	 */
	private $tag_cache;

	/**
	 * Zones helper instance
	 *
	 * @var Ads_Zone_Helper
	 */
	private $zone_helper;

	/**
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version     Version of the plugin.
	 */
	public function __construct($plugin_name, $version)
	{
		$this->setting_helper = new Ads_Settings_Helper($plugin_name);
		$this->tag_cache = new Ads_Tag_Cache($plugin_name, $version);
		$this->zone_helper = new Ads_Zone_Helper($plugin_name, $version);
	}

	/**
	 * Publish tags for ordinary zones
	 */
	public function publish_tags()
	{
		// do not publish tags if setting is activated and user is logged in
		if ( $this->setting_helper->is_ads_disabled_for_authorized_users() && is_user_logged_in() ) {
			return;
		}

		// Attributes missing from this whitelist are stripped by wp_kses and
		// break the rendered tag.
		$allowed_html = array(
			'script' => array(
				'type' => array(),
				'src' => array(),
				'async' => array(),
				'data-cfasync' => array(),
				'data-zone' => array(),
				'onerror' => array(),
				'onload' => array(),
			),
		);

		foreach ( Ads_Zone_Helper::get_allowed_directions() as $direction ) {
			// ignore not activated directions
			if (!$this->setting_helper->get_field_value( $direction, 'enabled') ) {
				continue;
			}
			$zone_id = $this->setting_helper->get_field_value( $direction, 'zone_id' );

			if ($direction === Ads_Zone_Helper::DIRECTION_PUSH_NOTIFICATION) {
				$this->tag_cache->ensure_service_worker( $zone_id );
			}

			// output sanitized tag `as is`
			echo wp_kses( $this->tag_cache->get( $zone_id ), $allowed_html ) . PHP_EOL;
		}
	}

	/**
	 * Insert meta tag with verification code
	 */
	public function insert_verification_code()
	{
		$verification_code = $this->setting_helper->get_verification_code();
		if ($verification_code !== false) {
			?>
			<meta name="monetag" content="<?php echo esc_attr( $verification_code ); ?>" />
			<?php
		}
	}
}
