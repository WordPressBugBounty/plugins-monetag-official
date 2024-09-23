<?php

class Ads_Options
{
	const SECTION_ID_GENERAL = 'general';
	const SECTION_ID_ZONES = 'zones';

	/**
	 * Plugin options prefix
	 *
	 * @var string
	 */
	private $prefix;

	/**
	 * Section id
	 *
	 * @var string
	 */
	private $section_id;

	public function __construct($plugin_name, $section_id)
	{
		$this->prefix = str_replace('-', '_', $plugin_name);
		$this->section_id = $section_id;
	}

	public function get_option($option_id)
	{
		return get_option($this->get_option_name($option_id));
	}

	public function update_option($option_id, $value)
	{
		update_option(
			$this->get_option_name($option_id),
			$value
		);
	}

	public function delete_option($option_id)
	{
		delete_option($this->get_option_name($option_id));
	}

	private function get_option_name($option_id)
	{
		return sprintf(
			'%s_%s_%s', $this->prefix, $this->section_id, $option_id
		);
	}
}
