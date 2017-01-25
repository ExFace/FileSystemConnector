<?php namespace exface\FileSystemConnector\QueryBuilders;

use exface\Core\CommonLogic\QueryBuilder\AbstractQueryBuilder;
use exface\Core\CommonLogic\AbstractDataConnector;
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
	
	/**
	 * 
	 * @return FileFinderDataQuery
	 */
	protected function build_query(){
		$query = new FileFinderDataQuery();
		
		// Look for filters, that can be processed by the connector itself
		foreach ($this->get_filters()->get_filters() as $qpart){
			if ($qpart->get_attribute()->get_id() == $this->get_main_object()->get_uid_attribute()->get_id()){
				switch ($qpart->get_comparator()){
					case EXF_COMPARATOR_IS:
					case EXF_COMPARATOR_EQUALS: 
						$path_pattern = Filemanager::path_normalize($qpart->get_compare_value());
						break;
					case EXF_COMPARATOR_IN:
						$values = explode(EXF_LIST_SEPARATOR, $qpart->get_compare_value());
						if (count($values) === 1){
							$path_pattern = Filemanager::path_normalize($values[0]);
							break;
						}
						// No "break;" here to fallback to default if none of the ifs above worked
					default: 
						$qpart->set_apply_after_reading(true);
						$query->set_full_scan_required(true);
				}
			} elseif ($qpart->get_attribute()->get_id() == $this->get_main_object()->get_label_attribute()->get_id()){
				switch ($qpart->get_comparator()){
					case EXF_COMPARATOR_IS: $filename = '/.*' . preg_quote($qpart->get_compare_value()) . './i'; break;
					default: //TODO
				}
			} else {
				$qpart->set_apply_after_reading(true);
				$query->set_full_scan_required(true);
			}
		}
		
		// Setup query
		$path_pattern = $path_pattern ? $path_pattern : $this->get_main_object()->get_data_address();
		$last_slash_pos = mb_strripos($path_pattern, '/');
		$path_relative = substr($path_pattern, 0, $last_slash_pos);
		$filename = $filename ? $filename : substr($path_pattern, ($last_slash_pos+1));
		
		if (count($this->get_sorters()) > 0){
			$query->set_full_scan_required(true);
		}
		
		$query->get_finder()->name($filename);
		$query->add_folder($path_relative);
		
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
		if ($files = $data_connection->query($query)->get_finder()){	
			$rownr = -1;
			$this->set_result_total_rows(count($files));
			foreach ($files as $file){
				// If no full scan is required, apply pagination right away, so we do not even need to reed the files not being shown
				if (!$query->get_full_scan_required()){
					$rownr++;
					// Skip rows, that are positioned below the offset
					if (!$query->get_full_scan_required() && $rownr < $this->get_offset()) continue;
					// Skip rest if we are over the limit
					if (!$query->get_full_scan_required() && $this->get_limit() > 0 && $rownr >= $this->get_offset() + $this->get_limit()) break;
				}
				// Otherwise add the file data to the result rows
				$result_rows[] = $this->build_result_row($file, $query);
			}
			$result_rows = $this->apply_filters($result_rows);
			$result_rows = $this->apply_sorting($result_rows);
			$result_rows = $this->apply_pagination($result_rows);
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
		$base_path = $query->get_base_path() ? $query->get_base_path() . '/' : '';
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