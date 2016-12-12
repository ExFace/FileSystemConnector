<?php namespace exface\FileSystemConnector\DataConnectors;

use exface\Core\Exceptions\DataConnectionError;
use exface\FileSystemConnector\FileContentsDataQuery;
use exface\Core\DataConnectors\TransparentConnector;
use exface\Core\Interfaces\DataSources\DataQueryInterface;

class FileContentsConnector extends TransparentConnector {
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\CommonLogic\AbstractDataConnector::perform_query()
	 * @return \SplFileInfo[]
	 */
	protected function perform_query(DataQueryInterface $query) {
		if (!($query instanceof FileContentsDataQuery)) throw new DataConnectionError('DataConnector "' . $this->get_alias_with_namespace() . '" expects an instance of FileContentsDataQuery as query, "' . get_class($query) . '" given instead!');
		
		return $query;
	}
}
?>