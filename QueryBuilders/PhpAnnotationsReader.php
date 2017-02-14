<?php namespace exface\FileSystemConnector\QueryBuilders;

use exface\Core\CommonLogic\QueryBuilder\AbstractQueryBuilder;
use exface\Core\CommonLogic\AbstractDataConnector;
use exface\FileSystemConnector\PhpAnnotationsDataQuery;
use Wingu\OctopusCore\Reflection\ReflectionMethod;
use Wingu\OctopusCore\Reflection\ReflectionClass;
use exface\Core\CommonLogic\Filemanager;
use exface\Core\Exceptions\QueryBuilderException;
use Wingu\OctopusCore\Reflection\ReflectionDocComment;
use exface\Core\Exceptions\DataSources\DataQueryFailedError;

/**
 * A query builder to read annotations for PHP classes, their methods and properties. Reads general comments and any specified annotation tags.
 * 
 * @uxon-config {
 * 	"annotation_level": "class|method|property",
 * 	"ignore_comments_without_matching_tags": false
 * }
 * 
 * @author Andrej Kabachnik
 *
 */
class PhpAnnotationsReader extends AbstractQueryBuilder {
	const ANNOTATION_LEVEL_METHOD = 'method';
	const ANNOTATION_LEVEL_CLASS = 'class';
	const ANNOTATION_LEVEL_PROPERTY = 'property';
	
	private $result_rows=array();
	private $result_totals=array();
	private $result_total_rows=0;
	private $last_query = null;
	
	/**
	 * 
	 * @return PhpAnnotationsDataQuery
	 */
	protected function build_query(){
		$query = new PhpAnnotationsDataQuery();
		$query->set_base_path($this->get_workbench()->filemanager()->get_path_to_vendor_folder());
		
		// Look for filters, that can be processed by the connector itself
		foreach ($this->get_filters()->get_filters() as $qpart){
			switch (mb_strtolower($qpart->get_attribute()->get_data_address())){
				case 'filename-relative': $query->set_path_relative($qpart->get_compare_value()); break;
				case 'filename': $query->set_path_absolute($qpart->get_compare_value()); break;
				case 'class': $query->set_class_name_with_namespace($qpart->get_compare_value()); break;
				case 'fqsen': 
					$class_name = substr($qpart->get_compare_value(), 0, strpos($qpart->get_compare_value(), '::'));
					if (strpos($class_name, '\\') !== 0){
						$class_name = '\\' . $class_name;
					}
					$query->set_class_name_with_namespace($class_name);
					// No break; here because we only use the beginning of the value, so the part after :: should be filtered after reading
				default: $qpart->set_apply_after_reading(true);
			}
		}
		
		// All the sorting must be done locally 
		foreach($this->get_sorters() as $qpart){
			$qpart->set_apply_after_reading(true);
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
	
	protected function get_annotation_level(){
		return $this->get_main_object()->get_data_address_property('annotation_level');
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\CommonLogic\QueryBuilder\AbstractQueryBuilder::read()
	 */
	public function read(AbstractDataConnector $data_connection = null){
		$result_rows = array();
		$annotation_level = $this->get_annotation_level();
		
		// Check if force filtering is enabled
		if (count($this->get_filters()->get_filters_and_nested_groups()) < 1){
			return false;
		}
		
		$query = $data_connection->query($this->build_query());
		$this->set_last_query($query);
		/* @var $class \Wingu\OctopusCore\Reflection\ReflectionClass */
		if ($class = $query->get_reflection_class()){
			// Read class annotations
			if (!$annotation_level || $annotation_level == $this::ANNOTATION_LEVEL_CLASS){
				$row = $this->build_row_from_class($class, array());
				if (count($row) > 0){
					$result_rows[] = $row;
				}
			}
			
			// Read method annotations
			if (!$annotation_level || $annotation_level == $this::ANNOTATION_LEVEL_METHOD){
				foreach ($class->getMethods() as $method){
					$row = $this->build_row_from_method($class, $method, array());
					if (count($row) > 0){
						$result_rows[] = $row;
					}
				}
			}
			
			// Read property annotations
			if (!$annotation_level || $annotation_level == $this::ANNOTATION_LEVEL_PROPERTY){
				if ($annotation_level == $this::ANNOTATION_LEVEL_PROPERTY){
					throw new QueryBuilderException('Annotations on property level are currently not supported in "' . get_class($this) . '"');
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
	
	/**
	 * 
	 * @param \ReflectionClass $class
	 * @param array $row
	 * @return string
	 */
	protected function build_row_from_class(\ReflectionClass $class, array $row){
		$file_pathname_absolute = $this->get_file_pathname_absolute($class);
		$file_pathname_relative = $this->get_file_pathname_relative($class);
		
		foreach ($this->get_attributes_missing_in_row($row) as $qpart){
			if (!$qpart->get_data_address()) continue;
			if (!array_key_exists($qpart->get_alias(), $row)){
				// First fill in the fields, any annotation row will need to know about it's class
				switch ($qpart->get_data_address()){
					case 'class': $row[$qpart->get_alias()] = $class->getName(); break;
					case 'namespace': $row[$qpart->get_alias()] = $class->getNamespaceName(); break;
					case 'filename': $row[$qpart->get_alias()] = $file_pathname_absolute; break;
					case 'filename-relative': $row[$qpart->get_alias()] = $file_pathname_relative; break;
				}
				
				// If we are specificlally interesten in the class annotations, search for fields
				// in the class comment specifically
				if ($this->get_annotation_level() == $this::ANNOTATION_LEVEL_CLASS){
					if ($comment = $class->getReflectionDocComment("\n\r\0\x0B")){
						$row = $this->build_row_from_comment_tags($class, $comment, $row);
						$row = $this->build_row_from_comment($class, $comment, $row);
					}
					// Add the FQSEN (Fully Qualified Structural Element Name) if we are on class level
					foreach ($this->get_attributes_missing_in_row($row) as $qpart){
						if (strcasecmp($qpart->get_data_address(), 'FQSEN') === 0){
							$row[$qpart->get_alias()] = $class->getName();
						}
					}
				}
			}
		}
		return $row;
	}
	
	/**
	 * 
	 * @param ReflectionClass $class
	 * @param ReflectionDocComment $comment
	 * @param unknown $row
	 * @return string
	 */
	protected function build_row_from_comment_tags(ReflectionClass $class, ReflectionDocComment $comment, $row){
		// Loop through all attributes to find exactly matching annotations
		foreach ($this->get_attributes_missing_in_row($row) as $qpart){
			// Only process attributes with data addresses
			if (!$qpart->get_data_address()) continue;
			// Do not overwrite already existent values (could happen when processing a parent class)
			if (array_key_exists($qpart->get_alias(), $row)) continue;
				
			// First look through the real tags for exact matches
			try {
				foreach($comment->getAnnotationsCollection()->getAnnotations() as $tag){
					if ($tag->getTagName() == $qpart->get_data_address()){
						$row[$qpart->get_alias()] = $tag->getDescription();
						break;
					}
				}
			} catch (\Exception $e){
				throw new DataQueryFailedError($this->get_last_query(), 'Cannot read annotation "' . $comment->getOriginalDocBlock(). '": ' . $e->getMessage(), null, $e);
			} catch (\ErrorException $e){
				throw new DataQueryFailedError($this->get_last_query(), 'Cannot read annotation "' . $comment->getOriginalDocBlock(). '": ' . $e->getMessage(), null, $e);
			}
		}
		return $row;
	}
	
	/**
	 * 
	 * @param ReflectionClass $class
	 * @param ReflectionMethod $method
	 * @param array $row
	 * @return string
	 */
	protected function build_row_from_method(ReflectionClass $class, ReflectionMethod $method, array $row){
		// First look for exact matches among the tags within the comment
		$comment = $method->getReflectionDocComment("\n\r\0\x0B");
		$row = $this->build_row_from_comment_tags($class, $comment, $row);
		
		// If at least one exact match was found, this method is a valid row.
		// Now add enrich the row with general comment fields (description, etc.) and fields from the class level
		if (!$this->get_ignore_comments_without_matching_tags() || count($row) > 0){
			$row = $this->build_row_from_class($class, $row);
			$row = $this->build_row_from_comment($class, $comment, $row);
			// Add the FQSEN (Fully Qualified Structural Element Name) if we are on method level
			foreach ($this->get_attributes_missing_in_row($row) as $qpart){
				if (strcasecmp($qpart->get_data_address(),'fqsen') === 0){
					$row[$qpart->get_alias()] = $class->getName() . '::' . $method->getName() . '()';
				}
			}
		}
	
		return $row;
	}
	
	/**
	 * 
	 * @param ReflectionClass $class
	 * @param ReflectionDocComment $comment
	 * @param array $row
	 * @return string
	 */
	protected function build_row_from_comment(ReflectionClass $class, ReflectionDocComment $comment, array $row){		
		foreach ($this->get_attributes_missing_in_row($row) as $qpart){
			if (!array_key_exists($qpart->get_alias(), $row)){
				switch ($qpart->get_data_address()){
					case 'desc': $row[$qpart->get_alias()] = $this->prepare_comment_text($comment->getFullDescription()); break;
					case 'desc-short': $row[$qpart->get_alias()] = $this->prepare_comment_text($comment->getShortDescription()); break;
					case 'desc-long': $row[$qpart->get_alias()] = $this->prepare_comment_text($comment->getLongDescription()); break;
				}
			}
		}
		return $row;
	}
	
	/**
	 * Removes single line breaks while leaving empty lines.
	 * 
	 * @param string $string
	 * @return string
	 */
	protected function prepare_comment_text($string){
		return preg_replace('/([^\r\n])\R([^{}\s\r\n])/', '$1$2', $string);
	}
	
	/**
	 * 
	 * @param array $row
	 * @return \exface\Core\CommonLogic\QueryBuilder\QueryPartAttribute[]
	 */
	protected function get_attributes_missing_in_row(array $row){
		$result = array();
		foreach ($this->get_attributes() as $qpart){
			// Only process attributes with data addresses
			if (!$qpart->get_data_address()) continue;
			// Do not overwrite already existent values (could happen when processing a parent class)
			if (array_key_exists($qpart->get_alias(), $row)) continue;
			// Otherwise add the query part to the resulting array
			$result[] = $qpart;
		}
		return $result;
	}
	
	/**
	 * 
	 * @param ReflectionClass $class
	 * @return string
	 */
	protected function get_file_pathname_relative(ReflectionClass $class){
		return Filemanager::path_normalize(str_replace($this->get_workbench()->filemanager()->get_path_to_vendor_folder() . DIRECTORY_SEPARATOR, '', $class->getFileName()));
	}
	
	/**
	 * 
	 * @param ReflectionClass $class
	 * @return string
	 */
	protected function get_file_pathname_absolute(ReflectionClass $class){
		return Filemanager::path_normalize($class->getFileName());
	}
	
	/**
	 * 
	 * @return boolean
	 */
	protected function get_ignore_comments_without_matching_tags(){
		return $this->get_main_object()->get_data_address_property('ignore_comments_without_matching_tags') ? true : false;
	}
	
	protected function get_last_query() {
		return $this->last_query;
	}
	
	protected function set_last_query(PhpAnnotationsDataQuery $value) {
		$this->last_query = $value;
		return $this;
	}
	
	  
}
?>