<?php namespace exface\FileSystemConnector\DataConnectors;

use exface\Core\CommonLogic\Filemanager;
use exface\FileSystemConnector\FileFinderDataQuery;
use exface\Core\DataConnectors\TransparentConnector;
use exface\Core\Interfaces\DataSources\DataQueryInterface;
use exface\Core\Exceptions\DataSources\DataConnectionQueryTypeError;
use exface\Core\Exceptions\DataSources\DataQueryFailedError;

class FileFinderConnector extends TransparentConnector {
	private $base_path = null;
	private $use_vendor_folder_as_base = false;
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\CommonLogic\AbstractDataConnector::perform_connect()
	 */
	protected function perform_connect() {
		if (!is_null($this->get_base_path())){
			$this->set_base_path($this->get_workbench()->filemanager()->get_path_to_base_folder());
		} elseif ($this->get_use_vendor_folder_as_base() != false){
			$this->set_base_path($this->get_workbench()->filemanager()->get_path_to_vendor_folder());
		} 
		return;
	}	

	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\CommonLogic\AbstractDataConnector::perform_query()
	 * 
	 * @param FileFinderDataQuery
	 * @return FileFinderDataQuery
	 */
	protected function perform_query(DataQueryInterface $query) {
		if (!($query instanceof FileFinderDataQuery)) throw new DataConnectionQueryTypeError($this, 'DataConnector "' . $this->get_alias_with_namespace() . '" expects an instance of FileFinderDataQuery as query, "' . get_class($query) . '" given instead!', '6T5W75J');
		
		$paths = array();
		// Prepare an array of absolut paths to search in
		foreach ($query->get_folders() as $path){
			if (!Filemanager::path_is_absolute($path) && !is_null($this->get_base_path())){
				$paths[] = Filemanager::path_join(array($this->get_base_path(), $path));
			} else {
				$paths[] = $path;
			}
		}
		
		// If the query does not have a base path, use the base path of the connection
		if (!$query->get_base_path()){
			$query->set_base_path($this->get_base_path());
		}
		
		// If no paths could be found anywhere (= the query object did not have any folders defined), use the base path
		if (count($paths) == 0){
			$paths[] = $query->get_base_path();
		}
		
		// Perform the search. This will fill the file and folder iterators in the finder instance. Thus, the resulting
		// files will be available through foreach($query as $splFileInfo) etc.
		try {
			$query->get_finder()->in($paths);
		} catch (\Exception $e){
			throw new DataQueryFailedError($query, "Data query failed!", null, $e);
			return array();
		}
		
		return $query;
	}
	  
	public function get_base_path() {
		return $this->base_path;
	}
	
	/**
	 * Sets the base path for the connection. If a base path is defined, all data addresses will be resolved relative to that path.
	 * 
	 * @uxon-property base_path
	 * @uxon-type string
	 * 
	 * @param unknown $value
	 * @return \exface\FileSystemConnector\DataConnectors\FileFinderConnector
	 */
	public function set_base_path($value) {
		if ($value){
			$this->base_path = Filemanager::path_normalize($value, '/');
		} else {
			$this->base_path = '';
		}
		return $this;
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public function get_use_vendor_folder_as_base() {
		return $this->use_vendor_folder_as_base;
	}
	
	/**
	 * Set to TRUE to use the current vendor folder as base path. 
	 * All data addresses in this conneciton will then be resolved relative to the vendor folder.
	 * 
	 * @uxon-property base_path
	 * @uxon-type boolean
	 * 
	 * @param boolean $value
	 * @return \exface\FileSystemConnector\DataConnectors\FileFinderConnector
	 */
	public function set_use_vendor_folder_as_base($value) {
		$this->use_vendor_folder_as_base = $value ? true : false;
		return $this;
	}  
  
}
?>