<?php namespace exface\FileSystemConnector\DataConnectors;

use exface\FileSystemConnector\FileContentsDataQuery;
use exface\Core\DataConnectors\TransparentConnector;
use exface\Core\Interfaces\DataSources\DataQueryInterface;
use exface\Core\CommonLogic\Filemanager;
use exface\Core\Exceptions\DataSources\DataConnectionQueryTypeError;
use exface\Core\Exceptions\DataSources\DataQueryFailedError;

class FileContentsConnector extends TransparentConnector {
	
	private $base_path = null;
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \exface\Core\CommonLogic\AbstractDataConnector::perform_connect()
	 */
	protected function perform_connect() {
		$base_path = $this->get_base_path();
		
		if (is_null($base_path)){
			$base_path = $this->get_workbench()->filemanager()->get_path_to_base_folder();
		}
		
		if (Filemanager::path_is_absolute($this->get_base_path())){
			$base_path = Filemanager::path_join($this->get_workbench()->filemanager()->get_path_to_base_folder(), $this->get_base_path());
		}
		
		$this->set_base_path($base_path);
		return;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\CommonLogic\AbstractDataConnector::perform_query()
	 * @return \SplFileInfo[]
	 */
	protected function perform_query(DataQueryInterface $query) {
		if (!($query instanceof FileContentsDataQuery)) throw new DataConnectionQueryTypeError($this, 'DataConnector "' . $this->get_alias_with_namespace() . '" expects an instance of FileContentsDataQuery as query, "' . get_class($query) . '" given instead!', '6T5W75J');
		
		// If the query does not have a base path, use the base path of the connection
		if (!$query->get_base_path()){
			$query->set_base_path($this->get_base_path());
		}
		
		if (!file_exists($query->get_path_absolute())){
			throw new DataQueryFailedError($query, 'File "' . $query->get_path_absolute() . '" not found!');
		}
		
		return $query;
	}
	
	/**
	 * Returns the current base path
	 * 
	 * @return string
	 */
	public function get_base_path() {
		return $this->base_path;
	}
	
	/**
	 * Sets the base path for the connection. If a base path is defined, all data addresses will be resolved relative to that path.
	 * 
	 * @uxon-property base_path
	 * @uxon-type string
	 * 
	 * @param string $value
	 * @return \exface\FileSystemConnector\DataConnectors\FileContentsConnector
	 */
	public function set_base_path($value) {
		if (!is_null($value)){
			$this->base_path = Filemanager::path_normalize($value, '/');
		} 
		return $this;
	}
  
}
?>