<?php namespace exface\FileSystemConnector\QueryBuilders;

use exface\Core\CommonLogic\QueryBuilder\AbstractQueryBuilder;
use exface\Core\CommonLogic\AbstractDataConnector;
use exface\FileSystemConnector\FileContentsDataQuery;
use League\Csv\Reader;
use SplFileObject;

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
		$query = $this->build_query();
		if (is_null($data_connection)){
			$data_connection = $this->get_main_object()->get_data_connection();
		}
		
		$data_connection->query($query);

		$field_map = array();
		foreach ($this->get_attributes() as $qpart){
			$field_map[$qpart->get_alias()] = $qpart->get_data_address();
		}

		// configuration
		$delimiter = $this->get_main_object()->get_data_address_property('DELIMITER') ? $this->get_main_object()->get_data_address_property('DELIMITER') : ',';
		$enclosure = $this->get_main_object()->get_data_address_property('ENCLOSURE') ? $this->get_main_object()->get_data_address_property('ENCLOSURE') : ',';

		// prepare filters
		foreach ($this->get_filters()->get_filters() as $qpart){
			$qpart->set_alias($field_map[$qpart->get_alias()]); // use numeric alias since league/csv filter on arrays with numeric indexes
			$qpart->set_apply_after_reading(true);
		}

		// prepare sorting
		foreach ($this->get_sorters() as $qpart){
			$qpart->set_alias($qpart->get_data_address());
			$qpart->set_apply_after_reading(true);
		}

		// prepare reader
		$csv = Reader::createFromPath(new SplFileObject($query->get_path_absolute()));
		$csv->setDelimiter($delimiter);
		$csv->setEnclosure($enclosure);

		// column count
		$colCount = count($csv->fetchOne());

		// add filter based on "normal" filtering
		$filtered = $csv;
		$filtered = $filtered->addFilter(function($row) {
			return parent::apply_filters(array($row));
		});

		// pagination
		$filtered->setOffset($this->get_offset());
		$filtered->setLimit($this->get_limit());

		// sorting
		$filtered->addSortBy(function ($row1, $row2) {
			$sorted = parent::apply_sorting(array($row1, $row2));
			if ($sorted[0] == $row1)
				return 1;
			else
				return -1;
		});

		$assocKeys = $this->getAssocKeys($colCount, $field_map);
		$result_rows = $filtered->fetchAssoc($assocKeys);
		$result_rows = iterator_to_array($result_rows);

		// row count
		$rowCount = $this->getRowCount($query->get_path_absolute(), $delimiter, $enclosure);
		$this->set_result_total_rows($rowCount);
		
		$this->set_result_rows($result_rows);
		return $this->get_result_total_rows();
	}

	protected function getAssocKeys($colCount, $field_map) {
		$keys = array_flip($field_map);

		$assocKeys = array();
		for ($i = 0; $i < $colCount; $i++) {
			if (isset($keys[$i]))
				$assocKeys[$keys[$i]] = $keys[$i];
			else
				$assocKeys[$i] = '- unused' . $i;    // unique value, not used by query
		}

		return $assocKeys;
	}

	/**
	 * Returns the row count after filtering the CSV.
	 * This has to be done on a separate CSV object. Otherwise the complete row count is returned instead of the
	 * filtered count.
	 *
	 * @param string $path path to CSV file
	 * @param string $delimiter delimiter character
	 * @param string $enclosure enclosure character
	 *
	 * @return int row count after filtering
	 */
	private function getRowCount($path, $delimiter, $enclosure) {
		$csv = Reader::createFromPath(new SplFileObject($path));
		$csv->setDelimiter($delimiter);
		$csv->setEnclosure($enclosure);

		// add filter based on "normal" filtering
		$filtered = $csv;
		$filtered = $filtered->addFilter(function($row) {
			return parent::apply_filters(array($row));
		});

		return $filtered->each(function ($row) {
			return true;
		});
	}
}
?>