<?php
namespace exface\FileSystemConnector\QueryBuilders;

use exface\FileSystemConnector\FileFinderDataQuery;
use Symfony\Component\Finder\SplFileInfo;

/**
 *
 * @author Andrej Kabachnik
 *        
 */
class PhpClassFinderBuilder extends FileFinderBuilder
{

    protected function getDataFromFile(SplFileInfo $file, FileFinderDataQuery $query)
    {
        $file_data = parent::getDataFromFile($file, $query);
        $file_data['class'] = str_replace(array(
            '/',
            '.php'
        ), array(
            '\\',
            ''
        ), $file_data['pathname_relative']);
        return $file_data;
    }
}
?>