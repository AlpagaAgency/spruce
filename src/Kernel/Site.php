<?php

namespace Spruce\Kernel;

use Spruce\Storage\Database;
use Spruce\HttpFoundation\Request;
use Spruce\Common\Collections\Collection;
use Spruce\Common\Shortcode as Shortcode;
use Spruce\Common\Helper as Helper;
use Spruce\Utility\StringTransformator;
use Spruce\Common\Helper\PolylangTranslationRegister;
use Spruce\Utility\Debug;

use Timber\Timber as Timber;
use Timber\Site as TimberSite;
use Timber\Image as TimberImage;
use Timber\Loader as TimberLoader;
use Timber\Menu as TimberMenu;

use Swift_SmtpTransport;
use Twig_Extension_StringLoader;
use Swift_Mailer;
use Twig_SimpleFunction;
use Twig_SimpleFilter;
use Exception;

class Site extends TimberSite {

	protected $menus = [
		'header_menu' => 'Header Menu',
		'social_menu' => 'Social Menu',
		'footer_menu' => 'Footer Menu',
	];

	protected $twigFolder = array('templates', 'views');

	protected $settings = [
		'Theme General Settings' => 'Theme Settings',
		'Social Settings' => 'Social Settings',
	];

	protected $factories = array();
	public $tplReference;
	protected $customTplReference = "layouts/custom.twig";
	protected $baseTplReference = "layouts/theme.twig";
	protected $useDefaultTheme = true;

	public function __construct() {
		$this->tplReference = $this->useDefaultTheme ? $this->baseTplReference : $this->customTplReference;
		$this->factories = new Collection();
		$this->add_theme_support();
		$this->add_actions();
		$this->remove_actions();
		$this->add_filters();
		$this->add_menu_location();
		$this->define_twig_folder();
		$this->add_factories();
		parent::__construct();
	}

	protected function add_theme_support()
	{
		add_theme_support( 'post-formats' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'menus' );
		add_theme_support( 'html5', array( 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption' ) );
		add_theme_support( 'custom-logo', array(
			'height'      => 100,
			'width'       => 400,
			'flex-height' => true,
			'flex-width'  => true,
			'header-text' => array( 'site-title', 'site-description' ),
		));

		return $this;
	}

	protected function add_factories()
	{

	}

	protected function add_actions()
	{
		add_action( 'widgets_init', array($this, 'register_widgets') );
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'init', function() {
			if (current_user_can('administrator')) 
			{
				wp_enqueue_style('admin-ff-styles', get_template_directory_uri() . '/app/static/styles.css');
			}
		} );
		add_action( 'init', array( $this, 'polylang_register_strings' ) );
		add_action( 'init', array( $this, 'add_options_page' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );
		add_action( 'admin_menu', array( $this, 'remove_menus' ) );
		add_action( 'init', array( $this, 'disable_emojis' ));
		add_action('admin_head', array( $this, 'admin_styles' ));
		add_action( 'login_enqueue_scripts', [$this, 'my_login_style'] );

		return $this;
	}

	protected function remove_actions()
	{
		remove_action('wp_head', 'wp_generator');

		return $this;
	}

	protected function add_filters () 
	{
		add_filter( 'timber_context', array( $this, 'add_to_context' ) );
		add_filter( 'get_twig', array( $this, 'add_to_twig' ) );
		add_filter('the_generator', function() {} );
		add_filter('show_admin_bar', '__return_false');
		add_filter('upload_mimes', array($this, 'add_mimes_types_for_upload'));
		add_filter( 'wpseo_metabox_prio', function() { return 'low'; } );
		add_filter( "login_headerurl", [$this, "custom_loginlogo_url"] );

		return $this;
	}

	function my_login_style()
	{
		print '<link rel="stylesheet" href="/app/themes/spruce/static/css/style.css" />';
	}

	public function custom_loginlogo_url($url) 
	{
		return "/";
	}

	public function define_twig_folder()
	{
		Timber::$dirname = $this->twigFolder;
	}

	public function admin_styles() {
		// wp_enqueue_style('admin-ff-styles', get_template_directory_uri() . '/static/css/admin.css');
	}

	public function polylang_register_strings() {
		if (function_exists('pll_the_languages')) {
			global $pagenow;
			if (is_admin() && $pagenow === 'admin.php' && isset($_GET['page'])) {
				if ($_GET['page'] === 'mlang_strings') {
					$helper = new PolylangTranslationRegister();
					$helper->run();
				}
			}
		}
	}

	public function register_widgets() {
		
	}

	public function add_options_page() {
		if( function_exists('acf_add_options_page') ) {
		 	foreach ($this->settings as $settingTitlePage =>  $settingMenuTitle):
		 		acf_add_options_sub_page(array(
					'page_title' 	=> $settingTitlePage,
					'menu_title' 	=> $settingMenuTitle,
					'parent_slug' 	=> "options-general.php",
				));
		 	endforeach;
		}
	}

	public function remove_menus(){
		// remove_menu_page( 'index.php' );                  //Dashboard
		remove_menu_page( 'jetpack' );                    //Jetpack* 
		// remove_menu_page( 'edit.php' );                   //Posts
		// remove_menu_page( 'upload.php' );                 //Media
		// remove_menu_page( 'edit.php?post_type=page' );    //Pages
		remove_menu_page( 'edit-comments.php' );          //Comments
		// remove_menu_page( 'themes.php' );                 //Appearance
		// remove_menu_page( 'plugins.php' );                //Plugins
		// remove_menu_page( 'users.php' );                  //Users
		// remove_menu_page( 'tools.php' );                  //Tools
		// remove_menu_page( 'options-general.php' );        //Settings
		// remove_menu_page( 'edit.php?post_type=acf-field-group' );        //ACF
	}

	protected function add_menu_location() {
		// add header_menu to Wordpress
		$menus = $this->menus;
		$tr = function_exists('pll_the_languages') ? "pll__" : "__";
		add_action('init', function() use ($tr, $menus) {
			foreach ($menus as $key => $menu):
				register_nav_menu($key, $tr( $menu ));
			endforeach;
		});

		// add header_menu to Timber Context
		add_filter('timber_context', function($data) use ($menus) {
			foreach ($menus as $key => $menu):
				$data[$key] = new TimberMenu($key);
			endforeach;
			//
			return $data;
		});
	}

	public function register_post_types() {
		//this is where you can register custom post types
	}

	public function register_taxonomies() {
		//this is where you can register custom taxonomies
	}

	function add_to_context( $context ) {
		$context['menu'] = new TimberMenu();
		$context['site'] = $this;
		if (function_exists("get_fields")) {
			if (get_fields("options") !== null)
			$context['options'] = get_fields('options');
		}
		
		if (function_exists("pll_current_language")) {
			$context["locale"] = pll_current_language();
		}

		if (function_exists('pll_the_languages')) {
			$context['language_switcher'] = pll_the_languages( $args = array(
				'dropdown'			   		=> 	0, // display as list and not as dropdown
				'echo'				   		=> 	0, // echoes the list
				'hide_if_empty'		  		=> 	1, // hides languages with no posts ( or pages )
				'show_flags'				=> 	0, // don't show flags
				'show_names'				=> 	1, // show language names
				'display_names_as'	   		=> 	'slug', // valid options are slug and name
				'force_home'				=> 	0, // tries to find a translation
				'hide_if_no_translation'	=> 	0, // don't hide the link if there is no translation
				'hide_current'		   		=> 	1, // don't hide current language
				'post_id'					=> 	null, // if not null, link to translations of post defined by post_id
				'raw'						=> 	0, // set this to true to build your own custom language switcher
			) );
		}

		$r = new Request();
		$context["realpath"] =  $r->getScheme() . "://" . $r->getHost() . $r->getUri();

		return $context;
	}

	function add_to_twig( $twig ) {
		/* this is where you can add your own functions to twig */
		$twig->addExtension( new Twig_Extension_StringLoader() );

		$twig->addFunction(new Twig_SimpleFunction('s', function() {
			// 
			print "<pre>";
			try {
				throw new Exception();
			} catch (Exception $e) {
				if (php_sapi_name() !== "cli") {
					header('Content-Type: text/html; charset=utf-8');
				}
				$trace = $e->getTrace();
				if (isset($trace[0])) {
					$file = realpath($trace[0]["file"]);
					$line = $trace[0]["line"];
					$where = sprintf("", $file, $line);
					$sep1 = str_repeat("─", mb_strlen($file) + 2)."┬".str_repeat("─", mb_strlen($line) + 2);
					$sep2 = str_repeat("─", mb_strlen($file) + 2)."┴".str_repeat("─", mb_strlen($line) + 2);
					printf("┌%s┐\n│ %s │ %d │\n└%s┘\n\n", $sep1, $file, $line, $sep2);
				}
			}
			foreach (func_get_args() as $value) {
				debug_zval_dump($value);
				print PHP_EOL;
			}
			print "</pre>";

			// exit(0);
		}));

		$twig->addFunction(new Twig_SimpleFunction('shuffle', function ($array) {
			shuffle($array);
			return $array;
		}));

		$twig->addFunction(new Twig_SimpleFunction('setUri', function($node,$variable) {
			$get = $_GET;
			$get[$node] = $variable;
			$str = [];
			foreach ($get as $id => $name) {
				if ($id == $node)
					continue;
			    $str[] = sprintf("%s=%s",$id,$name);
			}
			//
			$str[] = sprintf("%s=%s",$node,$variable);

			return parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH) . "?" . implode("&", $str);
		}));

		$twig->addFunction(new Twig_SimpleFunction('hasInUri', function($node,$variable) {
			return isset($_GET[$node]) && $_GET[$node] == $variable;
		}));

		$twig->addFunction(new Twig_SimpleFunction('get_field', function($fieldName, $id) {
			return get_field($fieldName, $id);
		}));

		$twig->addFunction(new Twig_SimpleFunction('pll__', function( $text ) {
				return function_exists('pll__')? pll__($text) : __($text);
		}));

		$twig->addFunction(new \Twig_SimpleFunction('pll_e', function( $text ) {
			return function_exists('pll_e') ? pll_e($text) : $text;
		} ));

		return $twig;
	}

		/**
	 * Disable the emoji's
	 */
	public function disable_emojis() {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' ); 
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' ); 
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'tiny_mce_plugins', array( $this, 'disable_emojis_tinymce') );
		add_filter( 'wp_resource_hints', array( $this, 'disable_emojis_remove_dns_prefetch'), 10, 2 );
	}

	/**
	 * Filter function used to remove the tinymce emoji plugin.
	 * 
	 * @param array $plugins 
	 * @return array Difference betwen the two arrays
	 */
	public function disable_emojis_tinymce( $plugins ) {
		if ( is_array( $plugins ) ) {
			return array_diff( $plugins, array( 'wpemoji' ) );
		} else {
			return array();
		}
	}

	/**
	 * Remove emoji CDN hostname from DNS prefetching hints.
	 *
	 * @param array $urls URLs to print for resource hints.
	 * @param string $relation_type The relation type the URLs are printed for.
	 * @return array Difference betwen the two arrays.
	 */
	public function disable_emojis_remove_dns_prefetch( $urls, $relation_type ) {
		if ( 'dns-prefetch' == $relation_type ) {
		/** This filter is documented in wp-includes/formatting.php */
			$emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/' );

			$urls = array_diff( $urls, array( $emoji_svg_url ) );
		}

		return $urls;
	}

	public function add_mimes_types_for_upload($mimes) 
	{
		$mimes['svg'] = 'image/svg+xml';
		return $mimes;
	}

	static public function get_acf_value($acf, $node) 
	{
		//
		if (!isset($acf[$node]))
			return [];
		//
		return $acf[$node]["value"];
	}

}
