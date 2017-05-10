<?php

namespace exface\FileSystemConnector\QueryBuilders;


use exface\Core\CommonLogic\Log\Helpers\LogHelper;
use exface\FileSystemConnector\FileContentsDataQuery;

class DailyLogfileCvsBuilder extends CsvBuilder {
	protected function build_query(){
		$query = new FileContentsDataQuery();
		$query->set_path_relative(LogHelper::getFilename($this->get_main_object()->get_data_address(), 'Y-m-d', '{filename}-{variable}'));
		return $query;
	}
}
