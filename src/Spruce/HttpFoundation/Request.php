<?php

namespace Spruce\HttpFoundation;

class Request {

	protected $collection;

	public function __construct() {
		$this->buildRequestCollection();
	}

	private function buildRequestCollection() {
		$out = array();
		foreach($_SERVER as $key=>$value) {
			if (substr($key,0,5)=="HTTP_") {
				$key=str_replace(" ","-",ucwords(strtolower(str_replace("_"," ",substr($key,5)))));
				$out[$key]=$value;
			}else{
				$out[$key]=$value;
			}
		}

		$this->collection = $out;
	}

	/*
	 * Grab method
	 */

	public function getCollection() {
		return $this->collection;
	}

	public function getMethod() {
		return $this->collection["REQUEST_METHOD"];
	}

	public function isPost() {
		return $this->collection["REQUEST_METHOD"] === "POST";
	}

	public function isGet() {
		return $this->collection["REQUEST_METHOD"] === "GET";
	}

	public function getUri($withParameters=true) {
		if ($withParameters) {
			return $this->collection["REQUEST_URI"];
		}
		$r = explode('?', $this->collection["REQUEST_URI"], 2);
		return $r[0];
	}

	public function getUriWithNewParams($params) {
		return sprintf(
			"%s?%s", 
			$this->getUri(false), 
			http_build_query(array_merge($this->getData(),$params))
		);
	}

	public function getContentType() {
		return $this->collection["CONTENT_TYPE"];
	}

	public function getContentLength() {
		return $this->collection["CONTENT_LENGTH"];
	}

	public function getQuery() {
		return $this->collection["QUERY_STRING"];
	}

	public function getHost() {
		return $this->collection["Host"];
	}

	public function getScheme() {
		return isset($this->collection["HTTPS"]) && $this->collection["HTTPS"] == "on"?"https":"http";
	}

	public function getStatus() {
		return $this->collection["REDIRECT_STATUS"];
	}

	public function get($key,$forcePost = false) {
		if ($this->isPost()) return $_POST[$key];
		else {
			if ($forcePost) throw new Exception("You must get this field only on POST Method");
			return isset($_GET[$key])?$_GET[$key]:null;
		}
	}

	public function getData() {
		if ($this->isPost()) return $_POST;
		else return $_GET;
	}

	public function isLocal() {
		return (isset($_SERVER["REMOTE_ADDR"]) && in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']));
	}

	public function isAjax() {
		return 'XMLHttpRequest' == $this->collection['X-Requested-With'];
	}

	public function has($key, $forcePost = false) {
		return !is_null($this->get($key, $forcePost));
	}

	public function selfRedirection() {
		return header("location: " . $this->getUri());
	}

	public function setRedirection($uri) {
		return header("location: " . $uri);
		die;
	}

}
