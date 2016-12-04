<?php namespace exface\FileSystemConnector\DataConnectors;

use exface\Core\CommonLogic\AbstractDataConnectorWithoutTransactions;
use Symfony\Component\Finder\SplFileInfo;
use exface\Core\CommonLogic\Filemanager;
use exface\Core\Exceptions\DataConnectionError;
use exface\FileSystemConnector\FileFinderDataQuery;

class FileFinderConnector extends AbstractDataConnectorWithoutTransactions {
	private $base_path = null;
	
	protected $last_error = null;
	
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
	 * @see \exface\Core\CommonLogic\AbstractDataConnector::perform_disconnect()
	 */
	protected function perform_disconnect() {
		return;
	}
	

	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\CommonLogic\AbstractDataConnector::perform_query()
	 * @return \SplFileInfo[]
	 */
	protected function perform_query($query, $options = null) {
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

	function get_insert_id() {
		// TODO
		return 0;
	}

	/**
	 * @name:  get_affected_rows_count
	 *
	 */
	function get_affected_rows_count() {
		// TODO
		return 0;
	}

	/**
	 * @name:  get_last_error
	 *
	 */
	function get_last_error() {
		if ($this->last_request){
			$error = "Status code " . $this->last_request->getStatusCode() . "\n" . $this->last_request->getBody();
		}
		return $error;
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