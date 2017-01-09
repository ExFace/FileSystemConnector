<?php namespace exface\FileSystemConnector;

use exface\Core\CommonLogic\Filemanager;
use Symfony\Component\Finder\Finder;
use exface\Core\CommonLogic\AbstractDataQuery;
use exface\Core\Widgets\DebugMessage;
use exface\Core\Factories\WidgetFactory;

class FileFinderDataQuery extends AbstractDataQuery {
	private $folders = array();
	private $basePath = null;
	private $query_builder = null;
	private $fullScanRequired = false;
	private $finder = null;
	
	/**
	 * 
	 * @return \Symfony\Component\Finder\Finder
	 */
	public function get_finder(){
		if (is_null($this->finder)){
			$this->finder = new Finder();
		}
		return $this->finder;
	}
	
	public function get_folders() {
		return $this->folders;
	}
	
	public function set_folders(array $patternArray) {
		$this->folders = $patternArray;
		return $this;
	}
	
	public function add_folder($relativeOrAbsolutePath){
		$this->folders[] = $relativeOrAbsolutePath;
		return $this;
	}
	
	public function get_base_path() {
		return $this->basePath;
	}
	
	public function set_base_path($absolutePath) {
		if (!is_null($absolutePath)){
			$this->basePath = Filemanager::path_normalize($absolutePath);
		}
		return $this;
	} 
	
	public function set_full_scan_required($value){
		$this->fullScanRequired = $value ? true : false;
		return $this;
	}
	
	public function get_full_scan_required(){
		return $this->fullScanRequired;
	}
	
	/**
	 * {@inheritDoc}
	 *
	 * The finder query creates a debug panel showing the dump of the symfony finder object.
	 *
	 * @see \exface\Core\CommonLogic\AbstractDataQuery::create_debug_widget()
	 */
	public function create_debug_widget(DebugMessage $debug_widget){
		$page = $debug_widget->get_page();
		$sql_tab = $debug_widget->create_tab();
		$sql_tab->set_caption('Finder');
		/* @var $sql_widget \exface\Core\Widgets\Html */
		$sql_widget = WidgetFactory::create($page, 'Html', $sql_tab);
		$sql_widget->set_value('<div style="padding:10px;"><pre>' . $this->dump_finder() . '</pre></div>');
		$sql_widget->set_width('100%');
		$sql_tab->add_widget($sql_widget);
		$debug_widget->add_tab($sql_tab);
		return $debug_widget;
	}
	
	protected function dump_finder(){
		ob_start();
		var_dump($this);
		return ob_get_clean();
	}
	
}
?>