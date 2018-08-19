<?php

namespace Spruce\Utility;

use Spruce\Utility\Utf8;

class StringTransformator {

	/**
	 * Cache
	 *
	 * @var array
	 */
	static private $cache = array(
		'camelize'     => array(0 => array(), 1 => array()),
	);
	/**
	 * Custom rules for camelizing a string
	 *
	 * @var array
	 */
	static private $camelize_rules = array();

	/**
	 *
	 *
	 */
	static public function makeUrlFriendly($string, $max_length=NULL, $delimiter=NULL)
	{
		// This allows omitting the max length, but including a delimiter
		if ($max_length && !is_numeric($max_length)) {
			$delimiter  = $max_length;
			$max_length = NULL;
		}

		$string = html_entity_decode(Utf8::ascii($string), ENT_QUOTES, 'UTF-8');
		$string = strtolower(trim($string));
		$string = str_replace("'", '', $string);

		if (!strlen($delimiter)) {
			$delimiter = '_';
		}

		$delimiter_replacement = strtr($delimiter, array('\\' => '\\\\', '$' => '\\$'));
		$delimiter_regex       = preg_quote($delimiter, '#');

		$string = preg_replace('#[^a-z0-9\-_]+#', $delimiter_replacement, $string);
		$string = preg_replace('#' . $delimiter_regex . '{2,}#', $delimiter_replacement, $string);
		$string = preg_replace('#_-_#', '-', $string);
		$string = preg_replace('#(^' . $delimiter_regex . '+|' . $delimiter_regex . '+$)#D', '', $string);

		$length = strlen($string);
		if ($max_length && $length > $max_length) {
			$last_pos = strrpos($string, $delimiter, ($length - $max_length - 1) * -1);
			if ($last_pos < ceil($max_length / 2)) {
				$last_pos = $max_length;
			}
			$string = substr($string, 0, $last_pos);
		}

		return $string;
	}

	/**
	 * Remove everything except number and letter and transform space onto underscore
	 *
	 * @return String
	 */
	static public function underscorize($string)
	{
		do {
			$old_string = $string;
			$string = preg_replace('/([a-zA-Z])([0-9])/', '\1_\2', $string);
			$string = preg_replace('/([a-z0-9A-Z])([A-Z])/', '\1_\2', $string);
		} while ($old_string != $string);
		return strtolower($string);
	}

	static public function camelize($string, $upper)
	{

		$upper = (int)(bool) $upper;
		if (isset(self::$cache['camelize'][$upper][$string])) {
			return self::$cache['camelize'][$upper][$string];
		}
		$original = $string;
		// Handle custom rules
		if (isset(self::$camelize_rules[$string])) {
			$string = self::$camelize_rules[$string];
			if ($upper) {
				$string = ucfirst($string);
			}
		} else {
			// Make a humanized string like underscore notation
			if (strpos($string, ' ') !== FALSE) {
				$string = strtolower(preg_replace('#\s+#', '_', $string));
			}
			// Check to make sure this is not already camel case
			if (strpos($string, '_') === FALSE) {
				if ($upper) {
					$string = ucfirst($string);
				}
			// Handle underscore notation
			} else {
				$string[0] = strtolower($string[0]);
				if ($upper) {
					$string = ucfirst($string);
				}
				$string = preg_replace_callback('#_([a-z0-9])#i', array('self', 'camelizeCallback'), $string);
			}
		}
		self::$cache['camelize'][$upper][$original] = $string;
		return $string;
	}

	/**
	 * A callback used by ::camelize() to handle converting underscore to camelCase
	 *
	 * @param array $match  The regular expression match
	 * @return string  The value to replace the string with
	 */
	static private function camelizeCallback($match)
	{
		return strtoupper($match[1]);
	}

	/**
	 * Forces use as a static class
	 *
	 * @return Utf8
	 */
	private function __construct() { }
}