<?php

/**
 * Helper functions for registering / rendering settings
 */
class Ads_Settings_Helper
{
	// Field types
	const FIELD_TYPE_CHECKBOX = 'checkbox';
	const FIELD_TYPE_INPUT_TEXT = 'input_text';
	const FIELD_TYPE_DROPDOWN = 'dropdown';
	const FIELD_TYPE_INPUT_HIDDEN = 'hidden';

	const OPTION_ID_TOKEN = 'token';
	const OPTION_ID_SITE_ID = 'publisher_site_id';
	const OPTION_ID_VERIFICATION_CODE = 'verification_code';
	const OPTION_ID_PUBLISHER_SITE_VERIFIED = 'publisher_site_verified';
	const OPTION_ID_DISABLE_ADS_FOR_AUTHORIZED_USERS = 'logged_in_disabled';

	/**
	 * @var string $settings_page The slug-name of the settings page
	 */
	private $settings_page;

	/**
	 * Options helper instance
	 *
	 * @var Ads_Options
	 */
	private $options;

	public function __construct($settings_page)
	{
		$this->settings_page = $settings_page;
		$this->options = new Ads_Options($settings_page, Ads_Options::SECTION_ID_GENERAL);
	}

	/**
	 * Get publisher AntiAdBlock token
	 *
	 * @return string
	 */
	public function get_anti_adblock_token()
	{
		return $this->options->get_option(self::OPTION_ID_TOKEN);
	}

	/**
	 * Store publisher AntiAdBlock token
	 *
	 * @param string $value
	 */
	public function set_anti_adblock_token($value)
	{
		$this->options->update_option(self::OPTION_ID_TOKEN, $value);
	}

	/**
	 * Get publisher site id
	 *
	 * @return string
	 */
	public function get_publisher_site_id()
	{
		return $this->options->get_option(self::OPTION_ID_SITE_ID);
	}

	/**
	 * Store publisher site id
	 *
	 * @param string $value
	 */
	public function set_publisher_site_id($value)
	{
		$this->options->update_option(self::OPTION_ID_SITE_ID, $value);
	}

	/**
	 * Get site verification code
	 *
	 * @return string|false
	 */
	public function get_verification_code()
	{
		return $this->options->get_option(self::OPTION_ID_VERIFICATION_CODE);
	}

	/**
	 * Store site verification code
	 *
	 * @param string $value
	 */
	public function set_verification_code($value)
	{
		$this->options->update_option(self::OPTION_ID_VERIFICATION_CODE, $value);
	}

	/**
	 * Is publisher site verified
	 *
	 * @return bool
	 */
	public function is_publisher_site_verified()
	{
		$opt = filter_var(
			$this->options->get_option(
				self::OPTION_ID_PUBLISHER_SITE_VERIFIED
			),
			FILTER_VALIDATE_BOOLEAN,
			FILTER_NULL_ON_FAILURE
		);

		return $opt !== null ? $opt : false;
	}

	/**
	 * Store site verification status
	 *
	 * @param bool $value
	 */
	public function set_is_publisher_site_verified($value)
	{
		$this->options->update_option(self::OPTION_ID_PUBLISHER_SITE_VERIFIED, $value);
	}

	public function is_ads_disabled_for_authorized_users()
	{
		return $this->options->get_option(self::OPTION_ID_DISABLE_ADS_FOR_AUTHORIZED_USERS);
	}

	/**
	 * Store publisher AntiAdBlock token
	 *
	 * @param string $value
	 */
	public function set_logged_in_disabled($value)
	{
		$this->options->update_option(self::OPTION_ID_DISABLE_ADS_FOR_AUTHORIZED_USERS, $value);
	}

	/**
	 * Get field (option) value
	 *
	 * @param int $section_id
	 * @param int $field_id
	 *
	 * @return mixed    Option value
	 */
	public function get_field_value($section_id, $field_id)
	{
		return get_option($this->get_field_id($section_id, $field_id));
	}

	/**
	 * Set field (option) value
	 *
	 * @param int    $section_id
	 * @param int    $field_id
	 * @param string $value
	 */
	public function set_field_value($section_id, $field_id, $value)
	{
		update_option($this->get_field_id($section_id, $field_id), $value);
	}

	/**
	 * Delete field (option)
	 *
	 * @param int $section_id
	 * @param int $field_id
	 *
	 * @return void
	 */
	public function delete_field($section_id, $field_id)
	{
		delete_option($this->get_field_id($section_id, $field_id));
	}

	private function get_section_id($id)
	{
		return sprintf('%s_%s', str_replace('-', '_', $this->settings_page), $id);
	}

	private function get_field_id($section_id, $field_id)
	{
		return sprintf('%s_%s', $this->get_section_id($section_id), $field_id);
	}

	/**
	 * Add settings section to plugin settings page
	 *
	 * @param array $config Key-value config (id, title)
	 */
	public function add_section($config)
	{
		add_settings_section(
			$this->get_section_id($config['id']),
			__($config['title'], $this->settings_page),   // TODO: is it ok for i18n tools?
			array($this, 'render_section'),   // TODO: Do we need to configure callback?
			$this->settings_page
		);
	}

	/**
	 * Register setting and setup field rendering / sanitization
	 *
	 * @param array $config Key-value config (type, id, title, section)
	 */
	public function add_field($config)
	{
		$field_id = $this->get_field_id($config['section'], $config['id']);
		$renderer_name = 'render_' . $config['type'];
		$args = array_merge($config, array(
			'id' => $field_id,
			'label_for' => $field_id,
			'value' => $this->get_field_value($config['section'], $config['id']),
		));

		add_settings_field(
			$field_id,
			__(isset($config['title']) ? $config['title'] : '', $this->settings_page),
			array($this, $renderer_name),
			$this->settings_page,
			$this->get_section_id($config['section']),
			$args
		);

		register_setting(
			$this->settings_page,
			$field_id,
			$this->get_sanitize_callback($config['type'])
		);

		if (isset($config['validate']) && $config['validate'] === true) {
			register_setting(
				$this->settings_page,
				$field_id,
				array($this, 'validate_' . $field_id)
			);
		}
	}

	private function get_sanitize_callback($type)
	{
		if ($type === self::FIELD_TYPE_CHECKBOX) {
			return 'intval';
		}

		return '';
	}

	/**
	 * Delete options after update token
	 */
	public function clear_zone_settings()
	{
		$directions = Ads_Zone_Helper::get_allowed_directions();

		array_walk($directions, function ($direction_name) {
			$this->delete_field($direction_name, 'enabled');
			$this->delete_field($direction_name, 'zone_id');
		});
	}

	/**
	 * Clear plugin general options
	 *
	 * @return void
	 */
	public function clear_plugin_options()
	{
		$this->options->delete_option(self::OPTION_ID_DISABLE_ADS_FOR_AUTHORIZED_USERS);
		$this->options->delete_option(self::OPTION_ID_VERIFICATION_CODE);
		$this->options->delete_option(self::OPTION_ID_SITE_ID);
		$this->options->delete_option(self::OPTION_ID_TOKEN);
	}

	public function get_enabled_directions()
	{
		$directions = array();
		foreach (Ads_Zone_Helper::get_allowed_directions() as $direction_name) {
			$directions[$direction_name] = $this->get_field_value($direction_name, 'enabled');
		}

		return $directions;
	}

	public function get_zones_directions()
	{
		$zones = array();
		foreach (Ads_Zone_Helper::get_allowed_directions() as $direction_name) {
			$zones[$direction_name] = $this->get_field_value($direction_name, 'zone_id');
		}

		return $zones;
	}
}
