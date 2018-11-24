<?php

namespace Spruce\Model;

use Timber\Timber as Timber;
use Timber\Post as TimberPost;

use Spruce\Model\Image;

class Post extends TimberPost {

	protected $acfFields = null;
    protected $factories = null;

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

	public function addFactories($factories) 
    {
        $this->factories = $factories;
        return $this;
    }

    protected function getAcfFields() 
    {
        if (is_null($this->acfFields))
        {
            $this->acfFields = get_field_objects($this->ID);
        }
        return $this->acfFields;
	}
	
	public function getTemplateType()
    {
        return str_replace([".php", "tpl-"], "", $this->_wp_page_template);
	}
	
	public function getPicture($pictureId)
    {
        return new Image($pictureId);
    }

    public function getACFValue($node)
    {
        $acf = $this->getAcfFields();
        if (!isset($acf[$node]))
            return [];
        return $acf[$node]["value"];
    }

}
