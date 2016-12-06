<?php namespace exface\FileSystemConnector;

use exface\Core\Interfaces\DataSources\DataQueryInterface;
use exface\Core\CommonLogic\QueryBuilder\AbstractQueryBuilder;
use exface\Core\CommonLogic\Filemanager;

class FileContentsDataQuery implements DataQueryInterface {
	
	private $query_builder = null;
	private $path_absolute = null;
	private $path_relative = null;
	private $base_path = null;
	
	public function __construct(AbstractQueryBuilder $query_builder){
		$this->set_query_builder($query_builder);
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \exface\Core\Interfaces\DataSources\DataQueryInterface::get_query_builder()
	 */
	public function get_query_builder() {
		return $this->query_builder;
	}
	
	/**
	 *
	 * @param AbstractQueryBuilder $value
	 * @return \exface\FileSystemConnector\FileFinderDataQuery
	 */
	public function set_query_builder(AbstractQueryBuilder $value) {
		$this->query_builder = $value;
		return $this;
	}
	
	public function get_path_absolute() {
		if (is_null($this->path_absolute)){
			$this->set_path_absolute(Filemanager::path_normalize($this->get_base_path() . '/' . $this->get_path_relative()));
		}
		return $this->path_absolute;
	}
	
	public function set_path_absolute($value) {
		$this->path_absolute = $value;
		return $this;
	}
	
	public function get_path_relative() {
		return $this->path_relative;
	}
	
	public function set_path_relative($value) {
		$this->path_relative = $value;
		return $this;
	}
	
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
		
}
?>