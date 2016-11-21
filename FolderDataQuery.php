<?php namespace exface\FileSystemConnector;

use exface\Core\CommonLogic\AbstractDataQuery;
use exface\Core\CommonLogic\Filemanager;

class FolderDataQuery extends AbstractDataQuery {
	private $folders = array();
	private $filename_pattern = '';
	private $base_path = null;
	private $properties_mappings = array();
	
	public function get_folders() {
		return $this->folders;
	}
	
	public function set_folders($value) {
		$this->folders = $value;
		return $this;
	}
	
	public function add_folder($name){
		$this->folders[] = $name;
		return $this;
	}
	
	public function get_filename_pattern() {
		return $this->filename_pattern;
	}
	
	public function set_filename_pattern($value) {
		$this->filename_pattern = $value;
		return $this;
	}
	
	public function get_base_path() {
		if (is_null($this->base_path) && $this->get_query_builder()){
			$this->set_base_path($this->get_query_builder()->get_workbench()->filemanager()->get_path_to_base_folder());
		}
		return $this->base_path;
	}
	
	public function set_base_path($value) {
		$this->base_path = Filemanager::normalize($value);
		//$this->base_path = $value;
		return $this;
	}  
	
	public function get_properties_mappings() {
		return $this->properties_mappings;
	}
	
	public function set_properties_mappings($value) {
		$this->properties_mappings = $value;
		return $this;
	}
	
	public function add_property_mapping($alias, $file_property){
		$this->properties_mappings[$alias] = $file_property;
	}
	
}
?>