<?php

namespace Spruce\Utility;

use Timber\Timber as Timber;

use Spruce\Engine\CustomPostType;
use Spruce\Common\Collections\Collection;
use Spruce\Model\Factory;
use Spruce\Model\Post as Post;
use Spruce\Model\Image as Image;
use Exception;

class ImportCommand {

	protected $factory = false;
	protected $factories;
	protected $data = array();
	protected $ACFdata = array();
	protected $commandType = "add";
	protected $postId = false;
	protected $postType = false;
	protected $pageTemplate = false;
	protected $hasTaxonomy = false;
	protected $image = null;
	protected $defaultData = [
		"title" => "post_title",
		"content" => "post_content",
	];

	protected $ACFFields = [];

	public function __construct(Collection $factories) 
	{
		$this->factories = $factories;
		$this->terms = new Collection();
	}

	public function commandType($type)
	{
		if (!in_array($type, ["add", "update"]))
		{
			throw new Exception(sprintf("The type %s must be one of these type: add, update", $type));
		}
		$this->commandType = $type;

		return $this;
	}


	public function definePostAs($type)
	{
		$this->postType = $type;
		return $this;
	}

	public function defineTemplateForPage($template)
	{
		$this->pageTemplate = $template;
		return $this;
	}

	public function definePostId($id) 
	{
		if ($this->commandType != "update") 
		{
			throw new Exception(sprintf("You cannot define id with this command type: %s", $this->commandType));
		}
		
		if ($this->factory && !$this->factory->hasPost($id)) 
		{
			throw new Exception(sprintf("The entity with id '%s' was not found inside the '%s' factory", $id, $this->factory->getName()));
		}

		$this->postId = $id;
		return $this;
	}

	public function defineFactory($factory)
	{
		$this->factory = $this->factories->get($factory);
		$this->ACFFields = $this->factory->getAllFields();
		return $this;
	}

	protected function setWpData($values)
	{
		// Retrieve 
		$defaultsColumns = ($this->factory != false 
			? $this->factory->getColumns()
			: Factory::getDefaultColumns()
		);

		$defaultsColumnsValues = ($this->factory != false 
			? $this->factory->getColumnsValues()
			: Factory::getDefaultColumnsValues()
		);

		$data = [];

		foreach ($values as $key => $value) {
			$k = $key;
			if (isset($defaultsColumns[$key]))
			{
				$k = $defaultsColumns[$key];
			}
			$data[$k] = $value;
		}

		$this->data = $this->commandType == "add"
			? array_merge($defaultsColumnsValues, $data)
			: $data
		;

		if ($this->postType != false)
		{
			$this->data["post_type"] = $this->postType;
		}

		if ($this->pageTemplate != false)
		{
			$this->data["page_template"] = $this->pageTemplate.".php";
		}

		return $this;
	}

	public function addTermsToPost($taxonomy, $values)
	{
		$this->hasTaxonomy = true;
		$this->terms->add($taxonomy, $values);
		return $this;
	}

	public function addFeaturedImage($path)
	{
		$this->image = $path;
		return $this;
	}

	public function resetData()
	{
		$this->data = array();
		$this->ACFdata = array();
		$this->image = null;
		$this->resetTerms();
	}

	public function resetTerms()
	{
		$this->terms->removeAll();
		$this->hasTaxonomy = false;
		return $this;
	}

	public function setData($wpData, $acfData=[])
	{
		$this->setWpData($wpData);
		$this->setACFData($acfData);
		return $this;
	}

	protected function setACFData($values)
	{
		foreach ($values as $key => $value) {
			$k = $this->getKeyFromACFName($key);
			if (!$k)
				continue;
			$this->ACFdata[$k] = $value;
		}
		return $this;
	}

	public function store() 
	{
		$method = $this->commandType;
		if ($method == "update") {
			$this->data["ID"] = $this->postId;
		}
		if ($this->hasTaxonomy)
		{
			$this->data["tax_input"] = $this->terms->getAll();
		}

		// s($this->data);

		$cid = Factory::$method($this->data);
		$id = $method == "update" ? $this->postId : $cid;

		foreach ($this->ACFdata as $key => $value) {
			update_field( $key, $value, $id );
		}

		$this->postId = $id;

		if (!is_null($this->image))
		{
			Image::createFeaturedImage($this->image, $id);
		}

		$this->resetData();
		
		return $this;
	}

	public function getPost()
	{
		$entity = $this->factory != false ? $this->factory->getEntity() : "Spruce\Model\Post";
		return new $entity($this->postId);
	}

	public function getKeyFromACFName($name) 
	{
		if ($this->factory != false) {
			return isset($this->ACFFields[$name]) ? $this->ACFFields[$name] : false;
		}

		return isset($this->ACFFields[$name]) ? $this->ACFFields[$name] : $this->getKeyFromACFNameEngine($name);
	}

	public function getKeyFromACFNameEngine($name, $postId=false) 
	{
		$key = false;
		$postId = acf_get_valid_post_id($postId);
		$args = array();
		if ($postId) {
			$args = array('postId' => $postId);
		}
		$fieldGroups = acf_get_field_groups($args);
		if (!count($fieldGroups)) {
			return $key;
		}

		foreach ($fieldGroups as $group) {
			$fields = acf_get_fields($group['key']);
			if (!count($fields)) {
				break;
			}
			foreach ($fields as $field) {
				if ($field['name'] == $name) {
					if (!isset($this->ACFFields[$name]))
					{
						$this->ACFFields[$name] = $field["key"];
					}
					return $field['key'];
				}
			}
		}
		return $key;
	}

}
