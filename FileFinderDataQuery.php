<?php namespace exface\FileSystemConnector;

use exface\Core\CommonLogic\Filemanager;
use Symfony\Component\Finder\Finder;
use exface\Core\Interfaces\DataSources\DataQueryInterface;
use exface\Core\CommonLogic\QueryBuilder\AbstractQueryBuilder;

class FileFinderDataQuery extends Finder implements DataQueryInterface {
	private $folders = array();
	private $basePath = null;
	private $query_builder = null;
	
	public function getFolders() {
		return $this->folders;
	}
	
	public function setFolders(array $patternArray) {
		$this->folders = $patternArray;
		return $this;
	}
	
	public function addFolder($relativeOrAbsolutePath){
		$this->folders[] = $relativeOrAbsolutePath;
		return $this;
	}
	
	public function getBasePath() {
		return $this->basePath;
	}
	
	public function setBasePath($absolutePath) {
		$this->basePath = Filemanager::path_normalize($absolutePath);
		return $this;
	} 
	
	public function get_query_builder() {
		return $this->query_builder;
	}
	
	public function set_query_builder(AbstractQueryBuilder $value) {
		$this->query_builder = $value;
		return $this;
	}
	
	  
	  
	
}
?>