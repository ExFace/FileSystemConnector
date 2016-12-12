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
	 * @return ReflectionClass
	 */
	protected function perform_query(DataQueryInterface $query) {
		if (!($query instanceof PhpAnnotationsDataQuery)) throw new DataConnectionError('DataConnector "' . $this->get_alias_with_namespace() . '" expects an instance of PhpAnnotationsDataQuery as query, "' . get_class($query) . '" given instead!');
		
		return new ReflectionClass($query->get_class_name_with_namespace());
		
	}
  
}
?>