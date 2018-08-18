<?php

namespace Spruce\Model;

use Timber\Timber as Timber;
use Timber\Post as TimberPost;

use Spruce\Model\Image;

class Post extends TimberPost {

	public function getLanguage() {
		if( function_exists('pll_get_post_language') ):
			return pll_get_post_language($this->id);
		endif;
	}

	public function exists()
	{
		return $this->post_status === "publish" || $this->post_status === "draft";
	}

	public function setFeaturedImage($path)
	{
		$postId = $this->ID;
		$attachId = Image::createFeaturedImage($path, $postId);
		return new Image($attachId);
	}

}
