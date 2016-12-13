<?php namespace exface\FileSystemConnector;

use exface\Core\Interfaces\DataSources\DataQueryInterface;
use exface\Core\CommonLogic\Filemanager;

class FileContentsDataQuery implements DataQueryInterface {
	
	private $base_path = null;
	private $path_absolute = null;
	private $path_relative = null;
	
	public function get_file_info(){
		return new \SplFileInfo($this->get_path_absolute());
	}
	
	public function get_base_path() {
		return $this->base_path;
	}
	
	public function set_base_path($absolute_path) {
		$this->base_path = Filemanager::path_normalize($absolute_path);
		return $this;
	}  
	
	public function get_path_absolute() {
		if (is_null($this->path_absolute)){
			if (!is_null($this->path_relative) && $this->get_base_path()){
				return Filemanager::path_join(array($this->get_base_path(), $this->get_path_relative()));
			}
		}
		return $this->path_absolute;
	}
	
	public function set_path_absolute($value) {
		$this->path_absolute =  Filemanager::path_normalize($value);
		return $this;
	}
	
	public function get_path_relative() {
		if (is_null($this->path_relative)){
			return $this->get_path_absolute() && $this->get_base_path() ? str_replace($this->get_base_path() . '/', '', $this->get_path_absolute()) : null;
		}
		return $this->path_relative;
	}
	
	public function set_path_relative($value) {
		$this->path_relative =  Filemanager::path_normalize($value);
		return $this;
	}
		
}
?>