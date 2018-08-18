<?php

namespace Spruce\Common\Collections;

use Exception;

class Collection {

	protected $collection = array();

	public function add($name, $collectionItem) {
		if (!isset($this->collection[$name]))
			$this->collection[$name] = $collectionItem;
		else throw new Exception(sprintf("The node %s already exists", ucfirst($name)));

	}

	public function remove($name) {
		if (isset($this->collection[$name]))
			unset($this->collection[$name]);
		else throw new Exception(sprintf("The node %s does not exist", ucfirst($name)));
	}

	public function overwrite($name, $collectionItem) {
		$this->collection[$name] = $collectionItem;
	}

	public function has($name) {
		return isset($this->collection[$name]);
	}

	public function get($name) {
		if (isset($this->collection[$name]))
			return $this->collection[$name];
		else throw new Exception(sprintf("The node %s does not exist", ucfirst($name)));
	}

	public function getAll() {
		return $this->collection;
	}

	public function removeAll() {
		$this->collection = array();
		return $this;
	}

}
