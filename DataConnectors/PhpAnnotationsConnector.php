<?php namespace exface\FileSystemConnector\DataConnectors;

use exface\Core\Exceptions\DataConnectionError;
use exface\FileSystemConnector\PhpAnnotationsDataQuery;
use Wingu\OctopusCore\Reflection\ReflectionClass;
use exface\Core\Interfaces\DataSources\DataQueryInterface;

class PhpAnnotationsConnector extends FileContentsConnector {

	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\CommonLogic\AbstractDataConnector::perform_query()
	 * 
	 */
	protected function perform_query(DataQueryInterface $query) {
		if (!($query instanceof PhpAnnotationsDataQuery)) throw new DataConnectionError('DataConnector "' . $this->get_alias_with_namespace() . '" expects an instance of PhpAnnotationsDataQuery as query, "' . get_class($query) . '" given instead!');
		
		if (!$query->get_base_path() && $this->get_config_value('base_path')){
			$query->set_base_path($this->get_config_value('base_path'));
		}
		
		$query->set_reflection_class(new ReflectionClass($query->get_class_name_with_namespace()));
		return $query;		
	}
  
}
?>