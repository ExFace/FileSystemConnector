<?php namespace exface\FileSystemConnector\QueryBuilders;

use exface\Core\CommonLogic\QueryBuilder\AbstractQueryBuilder;
use exface\Core\CommonLogic\AbstractDataConnector;
use exface\FileSystemConnector\FileContentsDataQuery;
use exface\Core\Exceptions\QueryBuilderException;

/**
 * A query builder to the raw contents of a file. This is the base for many specific query builders like the CsvBuilder, etc.
 * 
 *  
 * @author Andrej Kabachnik
 *
 */
class FileContentsBuilder extends AbstractQueryBuilder {
	
	private $result_rows=array();
	private $result_totals=array();
	private $result_total_rows=0;
	
	/**
	 * 
	 * @return FileContentsDataQuery
	 */
	protected function build_query(){
		$query = new FileContentsDataQuery();
		$query->set_path_relative($this->replace_placeholders_in_path($this->get_main_object()->get_data_address()));
		return $query;
	}
	
	public function get_result_rows(){
		return $this->result_rows;
	}
	
	public function get_result_totals(){
		return $this->result_totals;
	}
	
	public function get_result_total_rows(){
		return $this->result_total_rows;
	}
	
	public function set_result_rows(array $array){
		$this->result_rows = $array;
		return $this;
	}
	
	public function set_result_totals(array $array){
		$this->result_totals = $array;
		return $this;
	}
	
	public function set_result_total_rows($value){
		$this->result_total_rows = $value;
		return $this;
	}
	
	protected function get_file_property(FileContentsDataQuery $query, $data_address){
		switch (mb_strtoupper($data_address)){
			case '_FILEPATH':
				return $query->get_path_absolute();
			case '_FILEPATH_RELATIVE':
				return $query->get_path_relative();
			case '_CONTENTS':
				return file_get_contents($query->get_path_absolute());
			default:
				return false;
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\CommonLogic\QueryBuilder\AbstractQueryBuilder::read()
	 */
	public function read(AbstractDataConnector $data_connection = null){
		$result_rows = array();
		$query = $this->build_query();
		if (is_null($data_connection)){
			$data_connection = $this->get_main_object()->get_data_connection();
		}
		
		$data_connection->query($query);

		foreach ($this->get_attributes() as $qpart){
			if ($this->get_file_property($query, $qpart->get_data_address())){
				$result_rows[$qpart->get_alias()] = $this->get_file_property($query, $qpart->get_data_address());
			}
		}
		
		$this->set_result_total_rows(count($result_rows));
		
		$this->apply_filters($result_rows);
		$this->apply_sorting($result_rows);
		$this->apply_pagination($result_rows);
		
		$this->set_result_rows($result_rows);
		return $this->get_result_total_rows();
	}
	
	/**
	 * Looks for placeholders in the give path and replaces them with values from the corresponding filters.
	 * Returns the given string with all placeholders replaced or FALSE if some placeholders could not be replaced.
	 *
	 * @param string $path
	 * @return string|boolean
	 */
	protected function replace_placeholders_in_path($path){
		foreach ($this->get_workbench()->utils()->find_placeholders_in_string($path) as $ph){
			if ($ph_filter = $this->get_filter($ph)){
				if (!is_null($ph_filter->get_compare_value())){
					$path = str_replace('[#'.$ph.'#]', $ph_filter->get_compare_value(), $path);
				} else {
					throw new QueryBuilderException('Filter "' . $ph_filter->get_alias() . '" required for "' . $path . '" does not have a value!');
				}
			} else {
				// If at least one placeholder does not have a corresponding filter, return false
				throw new QueryBuilderException('No filter found in query for placeholder "' . $ph . '" required for "' . $path . '"!');
			}
		}
		return $path;
	}
}
?>