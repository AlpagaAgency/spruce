<?php

namespace Spruce\Model;

use Timber\Timber as Timber;
use Timber\Term as TimberTerm;

class Term extends TimberTerm {

	static public function create($name,$taxonomy,$parent=0)
	{
		if (self::exists(sanitize_title($name),$taxonomy))
		{
			$term = term_exists(sanitize_title($name),$taxonomy);
			return new Term($term["term_id"]);
		}

		$parentId = 0;
		if ($parent !== 0)
		{
			if (!self::exists(sanitize_title($parent),$taxonomy))
			{
				$parentTerm = self::create($parent, $taxonomy);
			}
			else 
			{
				$parentTerm = term_exists(sanitize_title($parent),$taxonomy);
			}
			$parentId = $parentTerm["term_id"];
		}

		$insertedTerm = wp_insert_term(
			$name,
			$taxonomy,
			array(
				"parent" => (int)$parentId,
			)
		);

		if (is_a($insertedTerm, "WP_Error")) {
			if(isset($insertedTerm->errors["term_exists"])) 
			{
				return new Term($insertedTerm->error_data["term_exists"]);
			}
		}

		return new Term($insertedTerm["term_id"]);
	}

	static public function exists($name, $taxonomy)
	{
		return !is_null(term_exists( $name, $taxonomy ));
	}

}
