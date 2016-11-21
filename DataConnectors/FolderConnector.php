<?php namespace exface\FileSystemConnector\DataConnectors;

use exface\Core\CommonLogic\AbstractDataConnectorWithoutTransactions;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use exface\FileSystemConnector\FolderDataQuery;

class FolderConnector extends AbstractDataConnectorWithoutTransactions {
	private $base_path = null;
	
	protected $last_error = null;
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\CommonLogic\AbstractDataConnector::perform_connect()
	 */
	protected function perform_connect() {
		
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
	 */
	protected function perform_query($query, $options = null) {
		$finder = new Finder();
		$rows = array();
		
		// Set filters
		$finder->name($query->get_filename_pattern());
		// Read the folders
		foreach ($finder->in($query->get_folders()) as $file){
			$rows[] = $this->get_data_from_file($file, $query);
		}
		
		return $rows;
	}
	
	protected function get_data_from_file(SplFileInfo $file, FolderDataQuery $query){
		$row = array();
		$base_path = $query->get_base_path();
		var_dump($base_path);
		$file_data = array(
				'filename' => $file->getFilename(),
				'extension' => $file->getExtension(),
				'path' => $file->getPath(),
				'path_relative' => $base_path ? str_replace($base_path, '', $file->getPath()) : $file->getRealPath(),
				'pathname' => $file->getRealPath(),
				'pathname_relative' => $base_path ? str_replace($base_path, '', $file->getRealPath()) : $file->getRealPath()
		);
		
		foreach ($file_data as $property => $value){
			foreach ($this->get_aliases_for_property($property, $query) as $alias){
				$row[$alias] = $value;
			}
		}
		
		return $row;
	}
	
	protected function get_aliases_for_property($property_name, FolderDataQuery $query){
		return array_keys($query->get_properties_mappings(), $property_name);
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
	  
}
?>