<?php namespace exface\FileSystemConnector\DataConnectors;

use exface\Core\Exceptions\DataConnectionError;
use exface\FileSystemConnector\FileContentsDataQuery;
use exface\Core\DataConnectors\TransparentConnector;
use exface\Core\Interfaces\DataSources\DataQueryInterface;
use exface\Core\CommonLogic\Filemanager;
use exface\Core\Exceptions\DataSources\DataConnectionQueryTypeError;

class FileContentsConnector extends TransparentConnector {
	
	private $base_path = null;
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\CommonLogic\AbstractDataConnector::perform_query()
	 * @return \SplFileInfo[]
	 */
	protected function perform_query(DataQueryInterface $query) {
		if (!($query instanceof FileContentsDataQuery)) throw new DataConnectionQueryTypeError($this, 'DataConnector "' . $this->get_alias_with_namespace() . '" expects an instance of FileContentsDataQuery as query, "' . get_class($query) . '" given instead!', '6T5W75J');
		
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