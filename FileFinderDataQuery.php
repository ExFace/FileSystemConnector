<?php namespace exface\FileSystemConnector;

use exface\Core\CommonLogic\Filemanager;
use Symfony\Component\Finder\Finder;
use exface\Core\CommonLogic\AbstractDataQuery;
use exface\Core\Widgets\DebugMessage;
use exface\Core\Factories\WidgetFactory;
use exface\Core\Interfaces\DebuggerInterface;

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
		$finder_tab = $debug_widget->create_tab();
		$finder_tab->set_caption('Finder');
		/* @var $finder_widget \exface\Core\Widgets\Html */
		$finder_widget = WidgetFactory::create($page, 'Html', $finder_tab);
		$finder_widget->set_value($this->dump_finder($debug_widget->get_workbench()->get_debugger()));
		$finder_widget->set_width('100%');
		$finder_tab->add_widget($finder_widget);
		$debug_widget->add_tab($finder_tab);
		return $debug_widget;
	}
	
	protected function dump_finder(DebuggerInterface $debugger){
		return $debugger->print_variable($this);
	}
	
}
?>