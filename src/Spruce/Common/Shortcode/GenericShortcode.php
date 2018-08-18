<?php

namespace Spruce\Common\Shortcode;

use Timber\Post as TimberPost;
use Timber\Image as TimberImage;
use Timber\Timber as Timber;
use StdClass;
use Routes;
use Spruce\HttpFoundation\Request;
use Spruce\Kernel\Site as Site;


class GenericShortcode {

	protected $context;

	public function __construct() 
	{
		add_shortcode( "form-event", [ $this, 'getEventForm' ]);
	}

	public function addContext($context) 
	{
		$this->context = $context;
	}

	public function getNewsSection($attrs = []) 
	{

		$atts = shortcode_atts(
			array(
				'title' => '',
			),
			$attrs
		);

		$type = $attrs["type"];

		if (isset($attrs["ajax"]) && $attrs["ajax"] == "false") {
			$posts_per_page = isset($attrs["item"])?$attrs["item"]:(int)$attrs["showmore"];
		} else {
			$posts_per_page = 50;
		}

		$args = array(
			'post_type' => "post",
			'post_status' => 'publish',
			"lang" => $this->context['locale'],
			'posts_per_page' => $posts_per_page,
			'meta_query'	=> array(
				'relation' => 'AND',
				array(
					'key'	  	=> 'acf_type',
					'value'	  	=> $type == "all" ? array( "bluefish", "sector" ) : $attrs["type"],
					'meta_compare' 	=> $type == "all" ? "IN" : "=",
				),
				array(
					'key'	  	=> 'acf_category',
					'value'	  	=> $attrs["category"],
					'compare' 	=> "=",
				),
			)
		);


		// /**
		//  * Filter if the category parameter exists
		//  */
		// if (isset($attrs["category"]) && !is_null($attrs["category"])) {
		// 	$args['tax_query'] = array(
		// 		array(
		// 			'taxonomy' => 'category',
		// 			'field'    => 'slug',
		// 			'terms'    => explode(",",$attrs["category"]),
		// 		),
		// 	);
		// }

		// $posts = Timber::get_posts();

		return Timber::fetch("helper/shortcodes/news-section.html.twig", [
			"newsShortcodes" => Timber::get_posts($args),
			"showmore" => (int)$attrs["showmore"],
			"attrs" => $attrs,
			"placeholder" => get_field("news_placeholder", "option"),
			"link" => get_field("posts_page_" . $this->context["locale"], "option"),
		]);
	}

}
