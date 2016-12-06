<?php namespace exface\FileSystemConnector\QueryBuilders;

use exface\Core\CommonLogic\QueryBuilder\AbstractQueryBuilder;
use exface\Core\CommonLogic\AbstractDataConnector;
use exface\FileSystemConnector\PhpAnnotationsDataQuery;
use Wingu\OctopusCore\Reflection\ReflectionMethod;
use Wingu\OctopusCore\Reflection\ReflectionClass;
use exface\Core\CommonLogic\Filemanager;

/**
 * 
 * @author Andrej Kabachnik
 *
 */
class PhpAnnotationsReader extends AbstractQueryBuilder {
	private $result_rows=array();
	private $result_totals=array();
	private $result_total_rows=0;
	private $request_uid_filter = null;
	
	/**
	 * 
	 * @return PhpAnnotationsDataQuery
	 */
	protected function build_query(){
		$query = new PhpAnnotationsDataQuery($this);
		
		// Look for filters, that can be processed by the connector itself
		foreach ($this->get_filters()->get_filters() as $qpart){
			switch ($qpart->get_attribute()->get_data_address()){
				case 'filename-relative': $query->set_file_path_relative($qpart->get_compare_value()); break;
				case 'filename': $query->set_file_path_absolute($qpart->get_compare_value()); break;
				case 'class': $query->set_class_name_with_namespace($qpart->get_compare_value()); break;
				default: $qpart->set_apply_after_reading(true);
			}
		}
		return $query;
	}
	
	function get_result_rows(){
		return $this->result_rows;
	}
	
	function get_result_totals(){
		return $this->result_totals;
	}
	
	function get_result_total_rows(){
		return $this->result_total_rows;
	}
	
	function set_result_rows(array $array){
		$this->result_rows = $array;
		return $this;
	}
	
	function set_result_totals(array $array){
		$this->result_totals = $array;
		return $this;
	}
	
	function set_result_total_rows($value){
		$this->result_total_rows = $value;
		return $this;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\CommonLogic\QueryBuilder\AbstractQueryBuilder::read()
	 */
	public function read(AbstractDataConnector $data_connection = null){
		$result_rows = array();
		// Check if force filtering is enabled
		if (count($this->get_filters()->get_filters_and_nested_groups()) < 1){
			return false;
		}
		
		$query = $this->build_query();
		/* @var $class \Wingu\OctopusCore\Reflection\ReflectionClass */
		if ($class = $data_connection->query($query)){
			foreach ($class->getMethods() as $method){
				$row = $this->build_result_row_from_method($class, $method);
				if (count($row) > 0){
					$result_rows[] = $row;
				}
			}
			$result_rows = $this->apply_filters($result_rows);
			$this->result_total_rows = count($result_rows);
			$result_rows = $this->apply_sorting($result_rows);
			$result_rows = $this->apply_pagination($result_rows);
		}
	
		if (!$this->get_result_total_rows()){
			$this->set_result_total_rows(count($result_rows));
		}		
		
		$this->set_result_rows($result_rows);
		return $this->get_result_total_rows();
	}
	
	protected function build_result_row_from_method(ReflectionClass $class, ReflectionMethod $method){
		$row = array();
		$row_addons = array();
		
		$file_pathname_absolute = Filemanager::path_normalize($class->getFileName());
		$file_pathname_relative = Filemanager::path_normalize(str_replace($this->get_workbench()->filemanager()->get_path_to_vendor_folder() . DIRECTORY_SEPARATOR, '', $class->getFileName()));
		
		// Loop through all attributes to find matching annotations
		foreach ($this->get_attributes() as $qpart){
			// Only process attributes with data addresses
			if ($field = $qpart->get_attribute()->get_data_address()){
				$comment = $method->getReflectionDocComment();		
				$match_found = false;
				
				// First look through the real tags for exact matches
				foreach($comment->getAnnotationsCollection()->getAnnotations() as $tag){
					if ($tag->getTagName() == $field){
						$row[$qpart->get_alias()] = $tag->getDescription();
						$match_found = true;
						break;
					}
				}
				
				// If no match was found, see if it is one of the implicit tags
				if (!$match_found){
					switch ($field){
						case 'desc': $row_addons[$qpart->get_alias()] = $comment->getFullDescription(); $match_found = true; break;
						case 'desc-short': $row_addons[$qpart->get_alias()] = $comment->getShortDescription(); $match_found = true; break;
						case 'desc-long': $row_addons[$qpart->get_alias()] = $comment->getLongDescription(); $match_found = true; break;
						case 'class': $row_addons[$qpart->get_alias()] = $class->getName(); $match_found = true; break;
						case 'namespace': $row_addons[$qpart->get_alias()] = $class->getNamespaceName(); $match_found = true; break;
						case 'filename': $row_addons[$qpart->get_alias()] = $file_pathname_absolute; $match_found = true; break;
						case 'filename-relative': $row_addons[$qpart->get_alias()] = $file_pathname_relative; $match_found = true; break;
					}
				}
			}
		}
		
		if (count($row) > 0){
			$row = array_merge($row, $row_addons);
		}
	
		return $row;
	}
}
?>