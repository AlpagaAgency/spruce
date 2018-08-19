<?php

namespace Spruce\Handling;

use Timber\Timber as Timber;
use Timber\Loader as TimberLoader;
use Spruce\Kernel\Site;
use Spruce\User;
use Timber\Helper as TimberHelper;
use Spruce\Utility\ImportCommand;

class Controller {

	protected $context;
	protected $user;
	protected $needAuthentication = false;
	protected $cacheTimeout = 600;

	public function beforeCall() {
		if ($this->needAuthentication && !$this->user->getLoggedIn()) {
			header("location: /");
			die();
		}
	}

	public function addToLayout() {
		$data = array();
		$data["site"] = $this->context["site"];
		if( function_exists('acf_add_options_page') ) {
			$data['options'] = get_fields('options');
		}
		return $data;
	}

	public function __construct($timberContext) {
		$this->context = $timberContext;
		$this->user = new User();
		$this->beforeCall();
	}

	public function render($view,$data=array(),$expires=false,$cache=TimberLoader::CACHE_TRANSIENT) {
		$layoutData = $this->addToLayout();
		$data = array_merge($data, $layoutData);
		$expires = $this->getRequest()->isLocal() ? false : $expires;
		return Timber::render($view, $data, $expires, $cache);
		die();
	}

	public function renderJSON($data,$statusCode=200)
	{
		@header( 'Content-Type: text/plain; charset=' . get_option( 'blog_charset' ) );
		print (json_encode($data));
		die();
	}

	public function renderRaw($view, $data, $type="text/html")
	{
		@header( 'Content-Type: '. $type .'; charset=' . get_option( 'blog_charset' ) );
		print Timber::fetch($view, $data);
		die();
	}

	public function renderJS($view, $data=array())
	{
		@header( 'Content-Type: application/javascript; charset=' . get_option( 'blog_charset' ) );
		$layoutData = $this->addToLayout();
		$data = array_merge($data, $layoutData);
		print Timber::fetch($view,$data);
		die();
	}

	public function getDb() {
		return $this->context["services"]->get("db");
	}

	public function getRequest() {
		return $this->context["services"]->get("request");
	}

	public function getSession() {
		return $this->context["services"]->get("session");
	}

	public function getMailer() {
		return $this->context["services"]->get("mailer");
	}

	public function getFactories() {
		return $this->context["services"]->get("factories");
	}

	public function getFactory($factory) {
		$factories = $this->context["services"]->get("factories");
		if($factories->has($factory)) {
			return $factories->get($factory);
		}
		if (WP_DEBUG) {
			return new \Exception("No factory {$factory} found");
		}
		return null;
	}

	public function getImporter() {
		return new ImportCommand($this->context["services"]->get("factories"));
	}

	protected function redirect($uri) {
		return header("location: " . $uri);
		die();
	}

	protected function getContext() {
		return Timber::get_context();
	}

	protected function requiredLoggedIn() {
		if (!$this->user->getLoggedIn()) {
			header("location: /");
			die();
		}
	}

	protected function transient($fn,$timeout=null) {
		$timeout = !is_null($timeout)?$timeout:$this->cacheTimeout;
		return TimberHelper::transient(
			$this->getRequest()->getUri(),
			$fn,
			$this->getRequest()->isLocal() ? false : $timeout
		);
	}

}
