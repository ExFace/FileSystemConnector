<?php namespace exface\FileSystemConnector;

use Wingu\OctopusCore\Reflection\ReflectionClass;

class PhpAnnotationsDataQuery extends FileContentsDataQuery {
	
	private $class_name_with_namespace = null;
	private $reflection_class = null; 
	
	public function get_class_name_with_namespace() {
		if (is_null($this->class_name_with_namespace)){
			return $this::get_class_from_file($this->get_path_absolute());
		}
		
		return $this->class_name_with_namespace;
	}
	
	public function set_class_name_with_namespace($value) {
		$this->class_name_with_namespace = $value;
		return $this;
	}   
	
	/**
	 * 
	 * @return \Wingu\OctopusCore\Reflection\ReflectionClass
	 */
	public function get_reflection_class() {
		return $this->reflection_class;
	}
	
	/**
	 * 
	 * @param ReflectionClass $value
	 * @return \exface\FileSystemConnector\PhpAnnotationsDataQuery
	 */
	public function set_reflection_class(ReflectionClass $value) {
		$this->reflection_class = $value;
		return $this;
	}  
	
	protected static function get_class_from_file($absolute_path){
		if (!file_exists($absolute_path) && !is_dir($absolute_path)){
			throw new \InvalidArgumentException('Cannot get class from file "' . $absolute_path . '" - file not found!');
			return null;
		}
		$fp = fopen($absolute_path, 'r');
		$class = $namespace = $buffer = '';
		$i = 0;
		while (!$class) {
			if (feof($fp)) break;
	
			$buffer .= fread($fp, 512);
			try {
				$tokens = @token_get_all($buffer);
			} catch (\ErrorException $e) {
				// Ignore errors of the tokenizer. Most of the errors will result from partial reading, when the read portion
				// of the code does not make sense to the tokenizer (e.g. unclosed comments, etc.)
			}
	
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