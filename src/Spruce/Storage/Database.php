<?php

namespace Spruce\Storage;

use PDO;

class Database {

	protected $connector;
	protected $transactionCounter = 0;

	public function __construct(\StdClass $config) {
		$options = array(
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		);

		if ($config->type === 'mysql') {
			/*$options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'set names utf8mb4';*/
		}

		$this->connector = new PDO(
			sprintf('%s:host=%s;dbname=%s', $config->type, $config->host, $config->name),
			$config->user,
			$config->pass,
			$options
		);
	}

	public function beginTransaction() {
		return $this->connector->beginTransaction();
	}

	public function commit() {
		return $this->connector->commit();
	}

	public function rollback() {
		return $this->connector->rollback();
	}

	public function getConnector() {
		return $this->connector;
	}

	public function prepare($query) {
		return $this->connector->prepare($query);
	}

	public function query($query, $arguments = array()) {
		$stmt = $this->prepare($query);
		$stmt->execute($arguments);
		return $stmt;
	}

	public function exec($query) {
		return $this->connector->exec($query);
	}

	public function getLastInsertId($name=null) {
		return $this->connector->lastInsertId($name);
	}
}
