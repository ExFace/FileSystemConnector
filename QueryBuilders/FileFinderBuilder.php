<?php namespace exface\FileSystemConnector\QueryBuilders;

use exface\Core\CommonLogic\QueryBuilder\AbstractQueryBuilder;
use exface\Core\CommonLogic\AbstractDataConnector;
use exface\Core\Exceptions\QueryBuilderException;
use exface\Core\CommonLogic\Filemanager;
use exface\FileSystemConnector\FileFinderDataQuery;
use Symfony\Component\Finder\SplFileInfo;

/**
 * 
 * @author Andrej Kabachnik
 *
 */
class FileFinderBuilder extends AbstractQueryBuilder {
	private $result_rows=array();
	private $result_totals=array();
	private $result_total_rows=0;
	private $request_uid_filter = null;
	
	/**
	 * 
	 * @return \exface\FileSystemConnector\FolderDataQuery
	 */
	protected function build_query(){
		$query = new FileFinderDataQuery();
		$query->set_query_builder($this);
		
		// Look for filters, that can be processed by the connector itself
		foreach ($this->get_filters()->get_filters() as $qpart){
			if ($qpart->get_attribute()->get_id() == $this->get_main_object()->get_uid_attribute()->get_id() 
			&& ($qpart->get_comparator() == EXF_COMPARATOR_EQUALS || $qpart->get_comparator() == EXF_COMPARATOR_IN || $qpart->get_comparator() == EXF_COMPARATOR_IS)){
				$path_pattern = ($this->get_main_object()->get_data_address_property('use_vendor_path_as_base') ? 'vendor/' : '') . $qpart->get_compare_value();
				$path_pattern = Filemanager::path_normalize($path_pattern);
			} elseif ($qpart->get_attribute()->get_id() == $this->get_main_object()->get_label_attribute()->get_id()){
				switch ($qpart->get_comparator()){
					case EXF_COMPARATOR_IS: $filename = '/.*' . preg_quote($qpart->get_compare_value()) . './i'; break;
					default: //TODO
				}
			}
		}
		
		// Setup query
		$path_pattern = $path_pattern ? $path_pattern : $this->get_main_object()->get_data_address();
		$last_slash_pos = mb_strripos($path_pattern, '/');
		$path_relative = substr($path_pattern, 0, $last_slash_pos);
		$path_absolute = $this->get_workbench()->filemanager()->get_path_to_base_folder() . DIRECTORY_SEPARATOR . $path_relative;
		$filename = $filename ? $filename : substr($path_pattern, ($last_slash_pos+1));
		
		if ($this->get_main_object()->get_data_address_property('use_vendor_path_as_base') != false){
			$query->setBasePath($this->get_workbench()->filemanager()->get_path_to_vendor_folder());
		}
		
		$query->name($filename);
		$query->addFolder($path_absolute);
		
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
	
	protected function apply_filters_to_result_rows($result_rows){
		if (!is_array($result_rows)){
			return $result_rows;
		}
		// Apply filters
		foreach ($this->get_filters()->get_filters() as $qpart){
			if (!$qpart->get_data_address_property('filter_localy')) continue;
			/* @var $qpart \exface\Core\CommonLogic\QueryBuilder\QueryPartFilter */
			foreach ($result_rows as $rownr => $row){
				// TODO make filtering depend on data types and comparators. A central filtering method for
				// tabular data sets is probably a good idea.
				switch ($qpart->get_comparator()){
					case EXF_COMPARATOR_IN:
						$match = false;
						$row_val = $row[$qpart->get_alias()];
						foreach (explode(',', $qpart->get_compare_value()) as $val){
							$val = trim($val);
							if (strcasecmp($row_val, $val) === 0) {
								$match = true;
								break;
							}
						}
						if (!$match){
							unset($result_rows[$rownr]);
						}
						break;
					case EXF_COMPARATOR_EQUALS:
						if (strcasecmp($row[$qpart->get_alias()], $qpart->get_compare_value()) !== 0) {
							unset($result_rows[$rownr]);
						}
						break;
					case EXF_COMPARATOR_EQUALS_NOT:
						if (strcasecmp($row[$qpart->get_alias()], $qpart->get_compare_value()) === 0) {
							unset($result_rows[$rownr]);
						}
						break;
					case EXF_COMPARATOR_IS:
						if (stripos($row[$qpart->get_alias()], $qpart->get_compare_value()) === false) {
							unset($result_rows[$rownr]);
						}
						break;
					case EXF_COMPARATOR_IS_NOT:
						if (stripos($row[$qpart->get_alias()], $qpart->get_compare_value()) !== false) {
							unset($result_rows[$rownr]);
						}
						break;
					case EXF_COMPARATOR_GREATER_THAN:
						if ($row[$qpart->get_alias()] < $qpart->get_compare_value()) {
							unset($result_rows[$rownr]);
						}
						break;
					case EXF_COMPARATOR_GREATER_THAN_OR_EQUALS:
							if ($row[$qpart->get_alias()] <= $qpart->get_compare_value()) {
								unset($result_rows[$rownr]);
							}
						break;
					case EXF_COMPARATOR_LESS_THAN:
						if ($row[$qpart->get_alias()] > $qpart->get_compare_value()) {
							unset($result_rows[$rownr]);
						}
						break;
					case EXF_COMPARATOR_LESS_THAN_OR_EQUALS:
						if ($row[$qpart->get_alias()] >= $qpart->get_compare_value()) {
							unset($result_rows[$rownr]);
						}
						break;
					default: 
						throw new QueryBuilderException('The filter comparator "' . $qpart->get_comparator() . '" is not supported by the QueryBuilder "' . get_class($this) . '"!');
				}
			}
		}
		return $result_rows;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \exface\Core\CommonLogic\QueryBuilder\AbstractQueryBuilder::read()
	 */
	public function read(AbstractDataConnector $data_connection = null){
		$result_rows = array();
		// Check if force filtering is enabled
		if ($this->get_main_object()->get_data_address_property('force_filtering') && count($this->get_filters()->get_filters_and_nested_groups()) < 1){
			return false;
		}
		
		$query = $this->build_query();
		if ($files = $data_connection->query($query)){			
			foreach ($files as $file){
				$result_rows[] = $this->build_result_row($file, $query);
			}
			$result_rows = $this->apply_filters_to_result_rows($result_rows);
		}
	
		if (!$this->get_result_total_rows()){
			$this->set_result_total_rows(count($result_rows));
		}
		
		$this->set_result_rows($result_rows);
		return $this->get_result_total_rows();
	}
	
	protected function build_result_row(SplFileInfo $file, FileFinderDataQuery $query){
		$row = array();
	
		$file_data = $this->get_data_from_file($file, $query);
		
		foreach ($this->get_attributes() as $qpart){
			if ($field = $qpart->get_attribute()->get_data_address()){
				if (array_key_exists($field, $file_data)){
					$value = $file_data[$field];
				} else {
					$method_name = 'get' . ucfirst($field);
					if (method_exists($file, $method_name)){
						$value = call_user_func(array($file, $method_name));
					}
				}
				$row[$qpart->get_alias()] = $value;
			}
		}
	
		return $row;
	}
	
	protected function get_data_from_file(SplFileInfo $file, FileFinderDataQuery $query){
		$base_path = $query->getBasePath() . '/';
		$path = Filemanager::path_normalize($file->getPath());
		$pathname = Filemanager::path_normalize($file->getPathname());
	
		$file_data = array(
				'name' => $file->getExtension() ? str_replace('.' . $file->getExtension(), '', $file->getFilename()) : $file->getFilename(),
				'path_relative' => $base_path ? str_replace($base_path, '', $path) : $path,
				'pathname_absolute' => $file->getRealPath(),
				'pathname_relative' => $base_path ? str_replace($base_path, '', $pathname) : $pathname
		);
	
		return $file_data;
	}
}
?>