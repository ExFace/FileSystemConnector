<?php namespace exface\FileSystemConnector\QueryBuilders;

use exface\Core\CommonLogic\QueryBuilder\AbstractQueryBuilder;
use exface\Core\CommonLogic\AbstractDataConnector;
use exface\Core\Exceptions\QueryBuilderException;
use exface\FileSystemConnector\FolderDataQuery;

/**
 * 
 * @author Andrej Kabachnik
 *
 */
class FolderQueryBuilder extends AbstractQueryBuilder {
	private $result_rows=array();
	private $result_totals=array();
	private $result_total_rows=0;
	private $request_uid_filter = null;
	
	/**
	 * 
	 * @return \exface\FileSystemConnector\FolderDataQuery
	 */
	protected function build_query(){
		$query = new FolderDataQuery($this);
		
		// Setup query
		$path_pattern = $this->get_main_object()->get_data_address();
		$last_slash_pos = mb_strripos($path_pattern, '/');
		$path_relative = substr($path_pattern, 0, $last_slash_pos);
		$path_absolute = $this->get_workbench()->filemanager()->get_path_to_base_folder() . DIRECTORY_SEPARATOR . $path_relative;
		$filename = substr($path_pattern, ($last_slash_pos+1));
		
		$query->set_filename_pattern($filename);
		$query->add_folder($path_absolute);
		
		// Add the attributes
		foreach ($this->get_attributes() as $qpart){
			$query->add_property_mapping($qpart->get_alias(), $qpart->get_data_address());
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
		
		if ($data = $data_connection->query($this->build_query())){			
			// Apply live filters
			$result_rows = $this->apply_filters_to_result_rows($data);
		}
	
		if (!$this->get_result_total_rows()){
			$this->set_result_total_rows(count($result_rows));
		}
		
		$this->set_result_rows($result_rows);
		return $this->get_result_total_rows();
	}
}
?>