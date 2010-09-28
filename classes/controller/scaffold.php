<?php defined('SYSPATH') OR die('No direct access allowed.');

Class Controller_Scaffold extends Controller {

	protected $column = 'cities';
	
	protected $auto_modeler = TRUE;
	
	protected $items_per_page = 15;
	
	protected $db_first = "";
	
	protected $header = Array();
	
	protected $header_html = "";
	
	protected function _get_schema() {
		if ( empty( $this->header ) )
		{
			$db = Database::instance()->list_columns( $this->column );
			foreach ( $db as $collum ) {
				array_push($this->header, $collum["column_name"]);
				if ( isset( $collum["key"] ) && $collum["key"] === "PRI" ) {
					$this->db_first = $collum["column_name"];
				};
			};
		}
	}
	
	protected function _get_header() {
		if ( empty( $this->header ) )
		{
			$this->_get_schema();
		};
		if ( empty( $this->header_html ) )
		{
			foreach ( $this->header as $item ) {
				$this->header_html .= "<th>" . $item . "</th>";
			};
		};
	}
	
	protected function _auto_model()
	{
		if ( $this->auto_modeler )
		{
			$class_name = $this->column;
			$has_directory = substr(strrchr($class_name, "_"), 1);
			$directory_name = "model";
			if ( empty($has_directory) )
			{
				$has_directory = "";
			} else {
				$directory_name .= DIRECTORY_SEPARATOR . str_replace(Array(substr(strrchr($class_name, "_"), 0),""), Array("","\\"),$class_name);
				$class_name = $has_directory;
			};
			$path = APPPATH.'classes'.DIRECTORY_SEPARATOR.$directory_name;
			$file = $path.DIRECTORY_SEPARATOR.$class_name.EXT;

			if ( ! file_exists($file) )
			{
				$db = Database::instance()->list_columns( $this->column );
				foreach ( $db as $collum ) {
					if ( isset( $_primary_key ) && ! isset( $_primary_val ) && $collum["type"] === "string" ) {
						$_primary_val = $collum["column_name"];
					};
					if ( isset( $collum["key"] ) && $collum["key"] === "PRI" ) {
						$_primary_key = $collum["column_name"];
					};
				};
				$model_container = "<?php defined('SYSPATH') or die('No direct access allowed.');
class Model_". ucfirst($this->column) ." extends ORM
{
	protected \$_db = 'default';
    protected \$_table_name  = '". $this->column ."';
    protected \$_primary_key = '$_primary_key';
    protected \$_primary_val = '$_primary_val';
 
    protected \$_table_columns = array(\n";
				foreach ( $db as $collum ) {
					$model_container .= "\t\t'". $collum["column_name"] ."' => array('data_type' => '". $collum["type"] ."', 'is_nullable' => ". ( ( $collum["is_nullable"] ) ? "TRUE" : "FALSE" ) ."),\n";
				};
				$model_container .= "\t);\n}";
				
				if ( ! is_dir($path) )
				{
					@mkdir($path);
				};
				file_put_contents($file, $model_container);
			};
		}
	}

	public function action_index()
	{
		if ( $this->column === "" ) {
			echo "<p>". __("Please, select a column") . "</p>";
			exit;
		};
		
		$this->_get_header();
		$this->_auto_model();
		
		$orm = ORM::factory($this->column);
		
		$controller = url::base() . request::instance()->controller;
		
		$pagination = Pagination::factory(array(
			'total_items'    => $orm->count_all(),
			'items_per_page' => $this->items_per_page
		));
		
		$query = $orm
			->limit( $pagination->items_per_page )
			->offset( $pagination->offset )
			->find_all();

		$result = Array();
		foreach( $query as $key ) {
			$key = $key->as_array();
			$item = Array();
			foreach ( $key as $value ) {
				array_push($item, $value);
			};
			
			$id = $key[$this->db_first];
			array_push($item, "<a href=\"$controller/edit/$id\">". __("Edit") ."</a> | <a href=\"$controller/delete/$id\">". __("Delete") ."</a>");	
			array_push($result, $item);
		};
		
		$data = Array(
			"db_first" => $this->db_first,
			"header" => $this->header_html,
			"pagination" => $pagination->render(),
			"content" => $result,
			"msg" => ( isset($_GET["msg"]) ? $_GET["msg"] : NULL )
		);
		
		echo View::factory("scaffold/index", $data)->render();
	}
	
	public function action_insert( $request = NULL )
	{
		if ( $request === "save" ) {
			$post = Validate::factory($_POST)->rule(TRUE, 'not_empty')->as_array();
			$post_key = array_keys( $post );
			$post_value = array_values( $post );

			$query = DB::insert($this->column, $post_key)
									->values($post_value)
									->execute();
										
			Request::instance()->redirect('scaffold/?msg='. __("Record Added Successfully") . '!');
		} else {
			$this->_get_header();
			$data = Array(
				"header" => $this->header,
				"first" => $this->db_first,
				"msg" => ( isset($_GET["msg"]) ? $_GET["msg"] : NULL )
			);
			echo View::factory("scaffold/insert", $data)->render();
		};
	}
	
	public function action_edit( $request )
	{
		$this->_get_header();
		$orm = ORM::factory($this->column, $request)->as_array();

		$data = Array(
			"request" => $request,
			"first" => $this->db_first,
			"content" => $orm
		);
		
		echo View::factory("scaffold/edit", $data)->render();
	}
	
	public function action_save()
	{
		$orm = ORM::factory($this->column, array_shift( $_POST ))->values( $_POST );
		if ($orm->check()) {
			$orm->save();
			Request::instance()->redirect('scaffold/?msg='. __('Record updated successfully') .'!');
		} else {
			$errors = $orm->validate()->errors();
			Request::instance()->redirect("scaffold/?msg=$errors&msgtype=error");
		}
	}
	
	public function action_delete($request)
	{
		$this->_get_header();
		
		$query = DB::delete( $this->column )
						->where($this->db_first, "=", $request)
						->execute();
		Request::instance()->redirect("scaffold/?msg=" . __("Registration $request successfully deleted") . "!");
	}

}

// end controller