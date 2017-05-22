<?php namespace exface\FileSystemConnector\QueryBuilders;

use exface\Core\CommonLogic\QueryBuilder\AbstractQueryBuilder;
use exface\Core\CommonLogic\AbstractDataConnector;
use exface\FileSystemConnector\FileContentsDataQuery;
use League\Csv\Reader;
use SplFileObject;

/**
 * A query builder to read CSV files.
 * 
 * Supported data address properties
 * - DELIMITER - defaults to comma (,)
 * - ENCLOSURE - defaults to double quotes (")
 * - HAS_HEADER_ROW - specifies if the file has a header row with coulumn titles or not. Defaults to no (0)
 *  
 * @author Andrej Kabachnik
 *
 */
class CsvBuilder extends FileContentsBuilder {
	
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

		$static_values = array();
		$field_map = array();
		foreach ($this->get_attributes() as $qpart){
			if ($this->get_file_property($query, $qpart->get_data_address()) !== false){
				$static_values[$qpart->get_alias()] = $this->get_file_property($query, $qpart->get_data_address());
			} else {
				$field_map[$qpart->get_alias()] = $qpart->get_data_address();
			}
		}

		// configuration
		$delimiter = $this->get_main_object()->get_data_address_property('DELIMITER') ? $this->get_main_object()->get_data_address_property('DELIMITER') : ',';
		$enclosure = $this->get_main_object()->get_data_address_property('ENCLOSURE') ? $this->get_main_object()->get_data_address_property('ENCLOSURE') : "'";
		$hasHeaderRow = $this->get_main_object()->get_data_address_property('HAS_HEADER_ROW') ? $this->get_main_object()->get_data_address_property('HAS_HEADER_ROW') : 0;

		// prepare filters
		foreach ($this->get_filters()->get_filters() as $qpart){
			if ($this->get_file_property($query, $qpart->get_data_address()) === false){
				$qpart->set_alias($qpart->get_data_address()); // use numeric alias since league/csv filter on arrays with numeric indexes
				$qpart->set_apply_after_reading(true);
			} else {
				// TODO check if the filters on file properties match. Only need to check that once, as the query onle deals with a single file
			}
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
		$offset = $hasHeaderRow ? $this->get_offset() + 1 : $this->get_offset();
		$filtered->setOffset($offset);
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
		if ($hasHeaderRow)
			$rowCount = max(0, $rowCount - 1);

		$this->set_result_total_rows($rowCount);
		
		// add static values
		foreach ($static_values as $alias => $val){
			foreach (array_keys($result_rows) as $row_nr){
				$result_rows[$row_nr][$alias] = $val;
			}
		}
		
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
