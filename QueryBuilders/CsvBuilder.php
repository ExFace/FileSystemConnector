<?php namespace exface\FileSystemConnector\QueryBuilders;

use exface\Core\CommonLogic\QueryBuilder\AbstractQueryBuilder;
use exface\Core\CommonLogic\AbstractDataConnector;
use exface\FileSystemConnector\FileContentsDataQuery;

/**
 * A query builder to read CSV files.
 * 
 *  
 * @author Andrej Kabachnik
 *
 */
class CsvBuilder extends AbstractQueryBuilder {
	
	private $result_rows=array();
	private $result_totals=array();
	private $result_total_rows=0;
	
	/**
	 * 
	 * @return FileContentsDataQuery
	 */
	protected function build_query(){
		$query = new FileContentsDataQuery();
		$query->set_path_relative($this->get_main_object()->get_data_address());
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
		
		$field_map = array();
		foreach ($this->get_attributes() as $qpart){
			$field_map[$qpart->get_alias()] = $qpart->get_data_address();
		}
		
		$delimiter = $this->get_main_object()->get_data_address_property('DELIMITER') ? $this->get_main_object()->get_data_address_property('DELIMITER') : ',';
		
		if (($handle = fopen($query->get_path_absolute(), "r")) !== FALSE) {
			while (($data = fgetcsv($handle, 1000, $delimiter, "'")) !== FALSE) {
				$result_row = array();
				foreach ($field_map as $fld => $col_no){
					$result_row[$fld] = $data[$col_no];
				}
				$result_rows[] = $result_row;
			}
			fclose($handle);
		}
				
		
		$this->set_result_total_rows(count($result_rows));
		
		$result_rows = $this->apply_filters($result_rows);
		$result_rows = $this->apply_sorting($result_rows);
		$result_rows = $this->apply_pagination($result_rows);		
		
		$this->set_result_rows($result_rows);
		return $this->get_result_total_rows();
	}
	
	public function apply_filters($row_array){
		foreach ($this->get_filters()->get_filters() as $qpart){
			$qpart->set_apply_after_reading(true);
		}
		return parent::apply_filters($row_array);
	}
	
	public function apply_sorting($row_array){
		foreach ($this->get_sorters() as $qpart){
			$qpart->set_apply_after_reading(true);
		}
		return parent::apply_sorting($row_array);
	}
}
?>