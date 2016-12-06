<?php namespace exface\FileSystemConnector;

use exface\Core\CommonLogic\QueryBuilder\AbstractQueryBuilder;
use exface\Core\CommonLogic\Filemanager;

class PhpAnnotationsDataQuery extends FileContentsDataQuery {
	
	private $class_name_with_namespace = null;
	private $file_path_absolute = null;
	
	public function __construct(AbstractQueryBuilder $query_builder){
		$this->set_query_builder($query_builder);
		$this->set_base_path($this->get_query_builder()->get_workbench()->filemanager()->get_path_to_vendor_folder());
	}
	
	public function get_class_name_with_namespace() {
		return $this->class_name_with_namespace;
	}
	
	public function set_class_name_with_namespace($value) {
		$this->class_name_with_namespace = $value;
		return $this;
	}
	
	public function get_file_path_absolute() {
		return $this->file_path_absolute;
	}
	
	public function set_file_path_absolute($value) {
		$this->file_path_absolute = $value;
		$this->set_class_name_with_namespace($this::get_class_from_file($value));
		return $this;
	}
	
	public function get_file_path_relative() {
		return $this->get_file_path_absolute() ? str_replace($this->get_base_path() . '/', '', $this->get_file_path_absolute()) : null;
	}
	
	public function set_file_path_relative($value) {
		$this->set_file_path_absolute(Filemanager::path_join(array($this->get_base_path(), Filemanager::path_normalize($value))));
		return $this;
	}    
	
	protected static function get_class_from_file($absolute_path){
		if (!file_exists($absolute_path)){
			throw new \InvalidArgumentException('Cannot get class from file "' . $absolute_path . '" - file not found!');
		}
		$fp = fopen($absolute_path, 'r');
		$class = $namespace = $buffer = '';
		$i = 0;
		while (!$class) {
			if (feof($fp)) break;
	
			$buffer .= fread($fp, 512);
			$tokens = token_get_all($buffer);
	
			if (strpos($buffer, '{') === false) continue;
	
			for (;$i<count($tokens);$i++) {
				if ($tokens[$i][0] === T_NAMESPACE) {
					for ($j=$i+1;$j<count($tokens); $j++) {
						if ($tokens[$j][0] === T_STRING) {
							$namespace .= '\\'.$tokens[$j][1];
						} else if ($tokens[$j] === '{' || $tokens[$j] === ';') {
							break;
						}
					}
				}
	
				if ($tokens[$i][0] === T_CLASS) {
					for ($j=$i+1;$j<count($tokens);$j++) {
						if ($tokens[$j] === '{') {
							$class = $tokens[$i+2][1];
						}
					}
				}
			}
		}
		return $namespace . '\\' . $class;
	}
		
}
?>