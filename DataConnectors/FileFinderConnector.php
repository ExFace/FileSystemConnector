<?php namespace exface\FileSystemConnector\DataConnectors;

use Symfony\Component\Finder\SplFileInfo;
use exface\Core\CommonLogic\Filemanager;
use exface\Core\Exceptions\DataConnectionError;
use exface\FileSystemConnector\FileFinderDataQuery;
use exface\Core\DataConnectors\TransparentConnector;
use exface\Core\Interfaces\DataSources\DataQueryInterface;

class FileFinderConnector extends TransparentConnector {
	private $base_path = null;
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\CommonLogic\AbstractDataConnector::perform_connect()
	 */
	protected function perform_connect() {
		if (!is_null($this->get_config_value('base_path'))){
			$this->set_base_path($this->get_workbench()->filemanager()->get_path_to_base_folder());
		} elseif ($this->get_config_value('use_vendor_folder_as_base') != false){
			$this->set_base_path($this->get_workbench()->filemanager()->get_path_to_vendor_folder());
		} else {
			$this->set_base_path($this->get_config_value('base_path'));
		}
		return;
	}	

	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\CommonLogic\AbstractDataConnector::perform_query()
	 * @return \SplFileInfo[]
	 */
	protected function perform_query(DataQueryInterface $query) {
		if (!($query instanceof FileFinderDataQuery)) throw new DataConnectionError('DataConnector "' . $this->get_alias_with_namespace() . '" expects an instance of FileFinderDataQuery as query, "' . get_class($query) . '" given instead!');
		
		$paths = array();
		// Prepare an array of absolut paths to search in
		foreach ($query->getFolders() as $path){
			if (!Filemanager::path_is_absolute($path)){
				$paths[] = Filemanager::path_join(array($this->get_base_path(), $path));
			} else {
				$paths[] = $path;
			}
		}
		
		// If the query does not have a base path, use the base path of the connection
		if (!$query->getBasePath()){
			$query->setBasePath($this->get_base_path());
		}
		
		// If no paths could be found anywhere (= the query object did not have any folders defined), use the base path
		if (count($paths) == 0){
			$paths[] = $query->getBasePath();
		}
		
		// Perform the search. This will fill the file and folder iterators in the finder instance. Thus, the resulting
		// files will be available through foreach($query as $splFileInfo) etc.
		try {
			$query->in($paths);
		} catch (\Exception $e){
			throw new DataConnectionError("Data query failed!", null, $e);
			return array();
		}
		
		return $query;
	}
	  
	public function get_base_path() {
		return $this->base_path;
	}
	
	public function set_base_path($value) {
		if ($value){
			$this->base_path = Filemanager::path_normalize($value, '/');
		} else {
			$this->base_path = '';
		}
		return $this;
	}
  
}
?>