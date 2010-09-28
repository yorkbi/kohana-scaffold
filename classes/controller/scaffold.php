<?php defined('SYSPATH') OR die('No direct access allowed.');

Class Controller_Scaffold extends Controller {

	protected $column = '';
	
	protected $auto_modeler = TRUE;
	
	protected $items_per_page = 15;
	
	protected $db_first = "";
	
	protected $header = Array();
	
	protected $header_html = "";
	
	protected function _get_schema( $force = FALSE ) {
		if ( empty( $this->header ) || $force )
		{
			$db = Database::instance()->list_columns( $this->column );
			$this->header = Array();
			foreach ( $db as $collum ) {
				array_push($this->header, $collum["column_name"]);
				if ( isset( $collum["key"] ) && $collum["key"] === "PRI" ) {
					$this->db_first = $collum["column_name"];
				};
			};
		}
	}
	
	protected function _auto_model( $model = NULL )
	{
		$success = FALSE;
		if ( $this->auto_modeler )
		{
			if ( $model !== NULL )
			{
				$model_tmp = $this->column = $model;
			};
			$class_name = $this->column;
			$directory_name = "model" . DIRECTORY_SEPARATOR;
			if ( preg_match("/_/i", $class_name) )
			{
				$directory_name = "model";
				$paths = explode("_", $class_name);
				$count = count($paths);
				while ( $count >= 1 )
				{
					$directory_name .= DIRECTORY_SEPARATOR . array_shift( $paths );
					$count = count($paths);
					if ( $count === 1 ) {
						$class_name = array_shift( $paths );
					};
				};
			};
			$path = APPPATH.'classes'.DIRECTORY_SEPARATOR.$directory_name;
			$file = $path.$class_name.EXT;

			if ( ! file_exists($file) )
			{
				$db = Database::instance()->list_columns( $this->column );
				$_primary_key = "";
				$_primary_val = "";
				foreach ( $db as $collum ) {
					if ( ! empty( $_primary_key ) && ! empty( $_primary_val ) && $collum["type"] === "string" ) {
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
					mkdir($path, 0777, TRUE);
				};
				file_put_contents($file, $model_container);
				$success = TRUE;
			};
			if ( isset($model_tmp) )
			{
				$this->column = $model_tmp;
			};
		}
		return $success;
	}

	protected function auto_modeler()
	{
		$i = 0;
		foreach ( Database::instance()->list_tables() as $item )
		{
			if ( $this->_auto_model( $item ) )
			{
				$i++;
			};
		};
		if ( $i > 0 )
		{
			Request::instance()->redirect("scaffold/?msg=$i new models");
		} else {
			Request::instance()->redirect("scaffold/?msg=No new model found&msgtype=notice");
		};
	}
	
	protected function delete_modeler()
	{
		Request::instance()->redirect("scaffold");
	}

	public function action_index()
	{
		$content = Array();
		
		if ( isset($_GET["auto_modeler"]) )
		{
			if ( empty( $_GET["auto_modeler"] ) )
			{
				$this->auto_modeler();
			} else {
				$this->auto_modeler( $_GET["auto_modeler"] );
			};
		};
		
		if ( isset($_GET["delete_modeler"]) )
		{
			if ( empty( $_GET["delete_modeler"] ) )
			{
				$this->delete_modeler();
			} else {
				$this->delete_modeler( $_GET["delete_modeler"] );
			};
		};
		
		$subPath =  ( isset($_GET["dir"]) ) ? $_GET["dir"] : "";
		$path = APPPATH.'classes' . DIRECTORY_SEPARATOR . "model" . DIRECTORY_SEPARATOR .$subPath;
		
		if ($handle = opendir($path)) {
			$files = Array();
			$directores = Array();
			while (FALSE !== ($file = readdir($handle))) {
				if ( preg_match("/".EXT."/i", $file) )
				{
					array_push($files, str_replace(EXT, "", $file) );
				} else if ( ! preg_match("/\./i", $file) ) {
					array_push($directores, str_replace(EXT, "", $file));
				};
			};
			closedir($handle);
			
			foreach ( $directores as $item )
			{
				$item_name = str_replace(Array($path, EXT), "", $item);
				// array_push( $content, HTML::anchor('scaffold?dir='.$item_name, "[+] " . ucfirst($item_name)) );
				// array_push( $content, "[+] " . ucfirst($item_name) );
			};
			
			foreach ( $files as $item )
			{
				$item_name = str_replace(Array($path, EXT), "", $item);
				array_push( $content, HTML::anchor('scaffold/list/'.$subPath.$item_name, ucfirst($item_name)) );
			};
		};
		
		if ( empty($content) )
		{
			$content = __("No models to list");
		};
		
		$data = Array(
			"content" => $content,
			"msg" => ( isset($_GET["msg"]) ? $_GET["msg"] : "" ),
			"msgtype" => ( isset($_GET["msgtype"]) ? $_GET["msgtype"] : "success" )
		);
		echo View::factory("scaffold/index", $data)->render();
	}
	
	public function action_list( $request = NULL )
	{
		if ( empty( $request ) )
		{
			Request::instance()->redirect('scaffold');
		};
		$this->column = ( isset( $request ) ) ? $request : $this->column;
		$this->_get_schema(TRUE);
		
		if ( $this->column === "" ) {
			echo "<p>". __("Please, select a column") . "</p>";
			exit;
		};
		
		$orm = ORM::factory($this->column);
		
		$controller = url::base() . request::instance()->controller;
		
		$this->items_per_page = ( isset( $_GET["items_per_page"] ) ) ? $_GET["items_per_page"] : $this->items_per_page;
		
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
			array_push($item, "<a href=\"$controller/edit/". $this->column ."/$id\">". __("Edit") ."</a> | <a href=\"$controller/delete/". $this->column ."/$id\"  class=\"delete\">". __("Delete") ."</a>");	
			array_push($result, $item);
		};
		
		$data = Array(
			"column" => ucfirst(str_replace("_"," ",$this->column)),
			"db_first" => $this->db_first,
			"header" => $this->header,
			"pagination" => $pagination->render(),
			"content" => $result,
			"msg" => ( isset($_GET["msg"]) ? $_GET["msg"] : NULL ),
			"msgtype" => ( isset($_GET["msgtype"]) ? $_GET["msgtype"] : "success" )
		);
		
		echo View::factory("scaffold/list", $data)->render();
	}
	
	public function action_insert( $request )
	{
		if ( $request === "save" ) {
			$this->column = $_POST["column"];
			unset( $_POST["column"] );
			$post = Validate::factory($_POST)->rule(TRUE, 'not_empty')->as_array();
			$post_key = array_keys( $post );
			$post_value = array_values( $post );

			$query = DB::insert($this->column, $post_key)
									->values($post_value)
									->execute();
										
			Request::instance()->redirect('scaffold/list/'. $this->column .'/?msg='. __("Record Added Successfully") . '!');
		} else {
			$this->column = $request;
			$this->_get_schema(TRUE);
			$data = Array(
				"column" => ucfirst(str_replace("_"," ",$this->column)),
				"header" => $this->header,
				"first" => $this->db_first,
				"msg" => ( isset($_GET["msg"]) ? $_GET["msg"] : NULL )
			);
			echo View::factory("scaffold/insert", $data)->render();
		};
	}
	
	public function action_edit( $request, $id )
	{
		$this->column = $request;
		$this->_get_schema(TRUE);
		
		$orm = ORM::factory($this->column, $id)->as_array();

		$data = Array(
			"column" => ucfirst($this->column),
			"request" => $id,
			"first" => $this->db_first,
			"content" => $orm
		);
		
		echo View::factory("scaffold/edit", $data)->render();
	}
	
	public function action_save()
	{
		$id = array_keys($_POST);
		$this->column = $_POST["column"];
		unset( $_POST["column"] );
		
		$orm = ORM::factory($this->column, array_shift( $_POST ))->values( $_POST );
		
		if ($orm->check()) {
			$orm->save();
			Request::instance()->redirect('scaffold/list/'. $this->column .'/?msg='. __('Record updated successfully') .'!');
		} else {
			$errors = $orm->validate()->errors();
			Request::instance()->redirect("scaffold/list/". $this->column . "/?msg=$errors&msgtype=error");
		}
	}
	
	public function action_delete($request, $id)
	{
		exit;
		$this->column = $request;
		$this->_get_schema();
		
		$orm = ORM::factory($this->column, $id)->delete();

		Request::instance()->redirect("scaffold/list/". $request ."/?msg=" . __("Registration $request successfully deleted") . "!&msgtype=error");
	}

}

// end controller