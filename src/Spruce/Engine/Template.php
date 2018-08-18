<?php

namespace Spruce\Engine;

use Timber\Timber as Timber;

/**
 * Adding a dynamic page template.
 */
class Template {

	/**
	 * Set template file constant.
	 */
	private $file;
	private $name;
	private $ctrl = "PageController";
	private $action;

	/**
	 * Fire the constructor up :)
	 */
	public function __construct($name, $file=null) 
	{
		$this->file = !is_null($file) ? $file : sprintf("%s.php", strtolower($name));
		$this->name = ucfirst($name);
		$this->action = sprintf("view%sAction", ucfirst($name));
		add_filter( 'theme_page_templates', array( $this, 'add' ) );
		add_action( 'template_redirect',    array( $this, 'useTpl' ) );
	}

	public function setController($name) 
	{
		$this->ctrl = $name;
		return $this;
	}

	public function setAction($action) 
	{
		$this->action = $action;
		return $this;
	}

	public function getAction() 
	{
		return $this->action;
	}

	public function getController() 
	{
		return $this->ctrl;
	}

	/**
	 * Add page templates.
	 *
	 * @param  array  $templates  The list of page templates
	 * @return array  $templates  The modified list of page templates
	 */
	public function add( $templates ) 
	{
		$templates[$this->file] = __( $this->name, 'plugin-slug' );
		return $templates;
	}

	/**
	 * Modify page content if using a specific page template.
	 */
	public function useTpl () 
	{
		// Prevent render Template when searching
		if(strlen(get_search_query())) return true;
		$page_template = get_post_meta( get_the_ID(), '_wp_page_template', true );
		if ( $this->file == $page_template ) {
			$this->send();
			// add_filter( 'the_content', array( $this, 'send' ) );
		}
	}

	public function getTemplateName() 
	{
		return $this->name;
	}

	/**
	 * Add special content to the top of the content area.
	 *
	 * @return string  $content  The modified page content
	 */
	public function send() 
	{
		// Flag Wordpress rules as fake Template
		add_filter('timber_context', function($data) {
			$data["isFakeTemplate"] = true;
			return $data;
		});

		$ctrl = sprintf("\\App\\Controller\\%s",$this->ctrl);
		$action = $this->action;
		$tpl = (new $ctrl(Timber::get_context()));
		return $tpl->$action();
	}

}