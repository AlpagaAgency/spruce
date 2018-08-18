<?php 

namespace Spruce\Common\Helper;

class PolylangTranslationRegister
{
	protected $group;

	protected $files_extensions = array(
		'php',
		'inc',
		'twig',
	);

	/**
	 * Constructor.
	 */
	public function __construct($group = null)
	{
		$this->group = $group;
	}

	/**
	 * Run plugin.
	 */
	public function run()
	{
		$themes = wp_get_themes();
		if (!empty($themes)) {
			foreach ($themes as $name => $theme) {
				$theme_path = $theme->theme_root . DIRECTORY_SEPARATOR . $name;
				$files = $this->get_files_from_dir($theme_path);
				$strings = $this->file_scanner($files);
				$this->add_to_polylang_register($strings, !is_null($this->group)?$this->group:$name);
			}
		}
	}

	/**
	 * Get files from dictionary recursive.
	 */
	protected function get_files_from_dir($dir_name)
	{
		$results = array();
		$files = scandir($dir_name);
		foreach ($files as $key => $value) {
			$path = realpath($dir_name . DIRECTORY_SEPARATOR . $value);
			if (!is_dir($path)) {
				$path_parts = pathinfo($path);
				if (!empty($path_parts['extension']) && in_array($path_parts['extension'], $this->files_extensions)) {
					$results[] = $path;
				}
			} else if ($value != "." && $value != "..") {
				$temp = $this->get_files_from_dir($path);
				$results = array_merge($results, $temp);
			}
		}
		return $results;
	}

	/**
	 *  Get strings from polylang methods.
	 */
	protected function file_scanner($files)
	{
		$strings = array();
		foreach ($files as $file) {
			preg_match_all("/pll_[_e][\s]*\([\s]*[\'\"](.*?)[\'\"][\s]*\)/s", file_get_contents($file), $matches);
			if (!empty($matches[1])) {
				$strings = array_merge($strings, $matches[1]);
			}
		}
		return $strings;
	}

	/**
	 * Add strings to polylang register.
	 */
	protected function add_to_polylang_register($strings, $context)
	{
		if (!empty($strings)) {
			foreach ($strings as $string) {
				pll_register_string($string, $string, $context);
			}
		}
	}
}
