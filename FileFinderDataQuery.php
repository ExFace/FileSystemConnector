<?php namespace exface\FileSystemConnector;

use exface\Core\CommonLogic\Filemanager;
use Symfony\Component\Finder\Finder;
use exface\Core\CommonLogic\AbstractDataQuery;

class FileFinderDataQuery extends AbstractDataQuery {
	private $folders = array();
	private $basePath = null;
	private $query_builder = null;
	private $fullScanRequired = false;
	private $finder = null;
	
	/**
	 * 
	 * @return \Symfony\Component\Finder\Finder
	 */
	public function get_finder(){
		if (is_null($this->finder)){
			$this->finder = new Finder();
		}
		return $this->finder;
	}
	
	public function get_folders() {
		return $this->folders;
	}
	
	public function set_folders(array $patternArray) {
		$this->folders = $patternArray;
		return $this;
	}
	
	public function add_folder($relativeOrAbsolutePath){
		$this->folders[] = $relativeOrAbsolutePath;
		return $this;
	}
	
	public function get_base_path() {
		return $this->basePath;
	}
	
	public function set_base_path($absolutePath) {
		if (!is_null($absolutePath)){
			$this->basePath = Filemanager::path_normalize($absolutePath);
		}
		return $this;
	} 
	
	public function set_full_scan_required($value){
		$this->fullScanRequired = $value ? true : false;
		return $this;
	}
	
	public function get_full_scan_required(){
		return $this->fullScanRequired;
	}
	
}
?>