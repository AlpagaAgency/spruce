<?php

namespace Spruce\Model;

// use Spruce\Engine\CustomPostType;
use PostTypes\PostType;
use PostTypes\Taxonomy;
use Timber\Timber as Timber;
use Spruce\Utility\Debug;

class Factory {

	protected $context;
	protected $name;
	protected $slug = null;
	protected $single = null;
	protected $plural = null;
	protected $support = array('title', 'editor', 'thumbnail');
	protected $cpt;
	protected $order = "DESC";
	protected $orderby = "title";
	protected $postsPerPage = 10;
	protected $hasArchive = false;
	protected $shuffle = false;
	protected $parentSlug = null;
	protected $showInMenu = true;
	protected $hasCategories = false;
	protected $entity = "Timber\Post";
	protected $categoryEntity = "Timber\Term";
	protected $taxonomies = [];
	protected $hierarchical = false;

	protected $defaultColumns = [
        'name' => 'post_name',
        'content' => 'post_content',
        'title' => 'post_title',
        'status' => 'post_status',
        'type' => 'post_type',
	];

	protected $defaultColumnsValues = [
        'post_status' => 'draft',
        'post_type' => 'post',
	];

	public function __construct($callItself=false)
	{
		if (!$callItself) {
			$name = $this->name;
			$single = !is_null($this->single)?$this->single:$this->name;
			$slug = !is_null($this->slug)?$this->slug:$this->name;
			$plural = !is_null($this->plural)?$this->plural:$name."s";
			$options = array(
				'name' => $name,
				'singular' => ucfirst($single),
				'plural' => ucfirst($plural),
				'slug' => $slug,
			);

			if (function_exists("pll_register_string")) {
				pll_register_string(ucfirst($single), ucfirst($single), "sprucefactory");
				pll_register_string(ucfirst($plural), ucfirst($plural), "sprucefactory");
				pll_register_string($name, $name, "sprucefactory");
			}

			$this->defaultColumnsValues["post_type"] = $this->name;

			if (!is_null($this->parentSlug)) {
				$options["parent_slug"] = $this->parentSlug;
			}

			$attributes = array(
				'has_archive' => $this->hasArchive,
				'show_in_menu' => $this->showInMenu,
			);
			if ($this->hierarchical) {
				$attributes["hierarchical"] = true;
				$this->support[] = "page-attributes";
			}
			$attributes['supports'] = $this->support;

			$this->cpt = new PostType($options, $attributes);

			if ($this->hasCategories) {
				$this->addCategories();
			}

			$this->init();
		}
	}

	public function getColumns()
	{
		return $this->defaultColumns;
	}

	public function getColumnsValues()
	{
		return $this->defaultColumnsValues;
	}

	static public function getDefaultColumns()
	{
		$s = new self(true);
		return $s->getColumns();
	}

	static public function getDefaultColumnsValues()
	{
		$s = new self(true);
		return $s->getColumnsValues();
	}

	public function getEntity() {
		return $this->entity;
	}

	public function hasPost($id)
	{
		return !(FALSE === get_post_status( $id )) && get_post_type( $id ) == $this->name;
	}

	public function create($values) {
		$values["post_type"] = $this->getName();
		$id = wp_insert_post($values);
		return new $this->entity($id);
	}

	static public function add($values)
	{
		// s($values);
		return wp_insert_post($values);
	}

	static public function update($values)
	{
		return wp_update_post($values);
	}

	public function findOneById($id) {
		$class = $this->entity;
		return new $class($id);
	}

	public function init()
	{

		if (function_exists('pll_the_languages')) {
			add_filter('pll_get_post_types', array( $this, 'setMultilingual' ));
		}

		$this->createShortCodes();
		$this->addACF();
		$this->cpt->register();
		foreach ($this->taxonomies as $key => $tax) 
		{
			$tax->posttype($this->name);
			$tax->register();
		}
	}

	public function addContext($context) {
		$this->context = $context;
	}

	public function setMultilingual($types) {
		return array_merge($types, array(
			$this->name => $this->name,
		));
	}

    public function getName() {
        return $this->name;
    }

    public function addACF()
    {

    }

    protected function addCategories($name=false,$singular=false,$plural=false,$slug=false)
    {
		$attrs = [
			"name" => ($name == false ? $this->name : $name) . 'category',
			"singular" => $singular == false ? 'Category' : $singular,
			"plural" => $plural == false ? 'Categories' : $plural,
			'slug' => $slug == false ? $name.'-category' : $slug
		];

		$tax = new Taxonomy($attrs);
		$this->taxonomies[$attrs["name"]] = $tax;
		return $this;
    }

    public function findPublished($limit=0) {
    	$limit = $limit != 0 ? $limit : $this->postsPerPage;
    	return Timber::get_posts(array(
    		'post_type' => $this->name,
			'post_status' => 'publish',
			'posts_per_page' => $limit,
			'order' => $this->order,
			"orderby" => $this->orderby,
			"paged" => get_query_var('paged')
    	), $this->entity);
	}
	
	public function findBy($by, $limit=0) {
		$limit = $limit != 0 ? $limit : $this->postsPerPage;
		$query = array_merge(array(
    		'post_type' => $this->name,
			'post_status' => 'publish',
			'posts_per_page' => $limit,
			'order' => $this->order,
			"orderby" => $this->orderby,
    	), $by);
    	return Timber::get_posts($query, $this->entity);
	}

    public function getAllFields()
    {
    	$fields = [];
    	$groups = acf_get_field_groups(array('post_type' => $this->name));
    	foreach ($groups as $group) {
    		foreach (acf_get_fields($group["key"]) as $field) {
    			$fields[$field["name"]] = $field["key"];
    		}
    	}
    	return $fields;
    }

    protected function findByCategoriesEngine($categorySlug,$limit,$categoryName, $exclude = false) {
    	$limit = $limit != 0 ? $limit : $this->postsPerPage;

    	$name = sprintf(
    		"%scategory",
    		$categoryName == false
    			? $this->name
    			: $categoryName
    	);

    	$categorySlug = is_array($categorySlug) ? $categorySlug : [$categorySlug];
		$args = array(
			'post_type' => $this->name,
			'post_status' => 'publish',
			'posts_per_page' => $limit,
			'order' => $this->order,
			"orderby" => $this->orderby,
			'tax_query' => array(
				array(
					'taxonomy' => $name,
					'field'    => 'term_id',
					'terms'    => $categorySlug,
				)
			)
		);
		
		if ($exclude != false) {
			$args["post__not_in"] = is_array($exclude) ? $exclude : [$exclude];
		}

		$test = Timber::get_posts($args, $this->entity);

    	return $test;
    }

    public function findAllByCategory($categorySlug,$limit=0,$categoryName=false, $exclude = false) {
    	return $this->findByCategoriesEngine($categorySlug,$limit,$categoryName, $exclude);
    }

    public function findAllByCategories($categoriesSlug,$limit=0,$categoryName=false, $exclude = false) {
    	return $this->findByCategoriesEngine($categoriesSlug,$limit,$categoryName, $exclude);
    }

    public function getCategories($name=false) {
    	$name = $name == false ? $this->name : $name;
    	return Timber::get_terms($name.'category');
	}
	
	public function count($type = "publish") 
	{
		if (in_array($type, [
			"publish",
			"future",
			"draft",
			"pending",
			"private",
			"trash",
			"auto-draft",
			"inherit",
			"request-pending",
			"request-confirmed",
			"request-failed",
			"request-completed",
			"acf-disabled",
		])) {
			$counts = wp_count_posts($this->name);
			return $counts->$type;
		}
		return null;
	}

	public function getPagination($limit = 0)
	{
		$limit = $limit == 0 ? $this->postsPerPage : $limit;
		$big = 999999999; // need an unlikely integer
		return paginate_links( array(
			'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
			'format' => '?paged=%#%',
			'current' => max( 1, get_query_var('paged') ),
			'total' => ceil($this->count() / $limit),
		) );
	}

    public function getGenericShortCode($attrs) {
		// Attributes
		$atts = shortcode_atts(
			array(
				'title' => '',
			),
			$attrs
		);

		$args = array(
			'post_type' => $this->name,
			'post_status' => 'publish',
			'posts_per_page' => $this->postsPerPage,
			'order' => $this->order,
			"orderby" => $this->orderby,
		);

		$posts = Timber::get_posts($args, $this->entity);

		if ($this->shuffle)
		{
			shuffle($posts);
		}

		return Timber::fetch(sprintf("helper/shortcodes/%s/list.html.twig", $this->name), array(
			"posts" => $posts,
			"attrs" => $atts,
		));
	}

	protected function createShortCodes() {
		add_shortcode( $this->name, array( $this, 'getGenericShortCode' ));
	}

	static public function getTranslatedPostIdFromId($id) {
		if( function_exists('pll_get_post') ):
			return pll_get_post($id);
		endif;
	}

	public function findByParent($parent = 0, $limit=0) {
    	$limit = $limit != 0 ? $limit : $this->postsPerPage;
    	return Timber::get_posts(array(
            'post_parent' => $parent,
    		'post_type' => $this->name,
			'post_status' => 'publish',
			'posts_per_page' => $limit,
			'order' => $this->order,
			"orderby" => $this->orderby,
			"paged" => get_query_var('paged')
    	), $this->entity);
    }
    
    public function findTopLevel($limit = 0) {
        return $this->findByParent(0, $limit);
	}

	protected function levelList($items) {
		$list = [];
		foreach ($items as $item) {
			$elm = [
				"id" => $item->ID,
				"title" => $item->title,
				"name" => $item->title,
			];
			$children = $item->getChildren();
			if(count($children)) {
				$elm["children"] = $this->levelList($children);
			}
			$list[] = $elm;
		}
		return $list;
	}
	
	public function findAllByLevel()
	{
		return $this->levelList($this->findByParent(0, -1));
	}

	// static public function getTranslatedPostFromId($id) {
	// 	if( function_exists('pll_get_post') ):
	// 		return new $this->entity(pll_get_post($id));
	// 	endif;
	// }
}


/************************************
** Sample ***************************
*************************************


<?php

namespace App\Model\Entity;

use StdClass;
use Timber\Post as TimberPost;
use Timber\Image as TimberImage;
use Timber\Timber as Timber;
use Routes;

use Spruce\Model\Entity;
use App\Site;
use Spruce\Utility\CsvGenerator;
use DateTime;

class ClientFactory extends Factory {

    protected $name = "client";
    protected $plural = "clients";
    protected $single = "client";
    protected $slug = "client";
    protected $postsPerPage = 50;
    protected $entity = "App\Model\Entity\Client";

}

***********************************
***********************************/
