<?php

namespace Spruce\HttpFoundation;

use Exception;
use WP_Session;


class Session {

	protected $session;

	public function __construct() {
		$this->session = WP_Session::get_instance();
		return $this;
	}

	public function set($name, $value) {
		$this->session[$name] = $value;
		return $this;
	}

	public function remove($name) {
		if (isset($this->session[$name]))
			unset($this->session[$name]);
		return $this;
	}

	public function has($name) {
		return isset($this->session[$name]);
	}

	public function get($name) {
		if (isset($this->session[$name]))
			return $this->session[$name];
		else return null;
	}

	public function getOnce($name) {
		if (isset($this->session[$name])) {
			$n = $this->session[$name];
			unset($this->session[$name]);
			return $n;
		}
		else return null;
	}

	public function reset() {
		$this->session->reset();
		return $this;
	}

	public function getAll() {
		return json_decode($this->session->json_out(), true);
	}
}
