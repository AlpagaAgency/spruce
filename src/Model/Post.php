<?php

namespace Spruce\Model;

use Timber\Timber as Timber;
use Timber\Post as TimberPost;

use Spruce\Model\Image;
use Spruce\Kernel\Site;

class Post extends TimberPost {

	protected $acfFields = null;
    protected $factories = null;
    protected $parentsIds = null;
    protected $ancestors = null;
    protected $picture = null;

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

    public function getImage() {
        if (is_null($this->picture)) {
            if ($this->thumbnail) {
                $this->picture = $this->thumbnail;
            } else {
                if ($this->hasParent()) {
                    foreach ($this->getAllAncestorPosts() as $parent) {
                        if ($parent->thumbnail) {
                            $this->picture = $parent->thumbnail;
                            break;
                        }
                    }
                }
            }
        }
        if (is_null($this->picture)) {
            $this->picture = new Image(get_field("placeholder", 'options'));
        }
        return $this->picture;
    }
    
    public function getChildren() 
    {
        return Timber::get_posts([
            "numberposts" => -1,
            "post_status" => "publish",
            "post_type" => $this->post_type,
            "post_parent" => $this->ID,
            "suppress_filters" => false,
        ], $this->getPostTypeClassName());
    }

    protected function getPostTypeClassName() {
        return isset(Site::POST_TYPES[$this->post_type]) 
        ? Site::POST_TYPES[$this->post_type] 
        : Site::POST_TYPES["default"];
    }

    public function getParent() {
        $class = $this->getPostTypeClassName();
        $parent = new $class($this->post_parent);
        return $parent;
    }

    public function getAllAncestorPosts() {
        if (is_null($this->ancestors) && $this->hasParent()) {
            $class = $this->getPostTypeClassName();
            $ancestors = [];
            $i = 0;
            $elm = $this;
            do {
                $elm = new $class($elm->post_parent);
                $ancestors[$elm->ID] = $elm;
            } while ($elm->hasParent());
            $this->ancestors = array_reverse($ancestors);
        }
        return $this->ancestors;
    }

    public function hasParent() {
        return $this->post_parent != 0;
    }

    public function getParents() {
        if (is_null($this->parentsIds)) {
            $this->parentsIds = get_post_ancestors($this->ID);
        }
        return $this->parentsIds;
    }

}
