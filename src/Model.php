<?php
//
// Company: Cloudmanic Labs, LLC
// By: Spicer Matthews 
// Email: spicer@cloudmanic.com
// Website: http://cloudmanic.com
// Date: 1/19/2016
// Note: Non-Cloudmanic Product Version.
//

namespace Cloudmanic\LaravelApi;

use DB;
use Auth;

class Model
{	
	public $table = '';
	public $table_prefix = null;
	public $table_id_col = 'Id';
	public $table_account_col = 'AccountId';	
	public $connection = 'mysql';
	public $joins = null;
	public $export_cols = [];
	public $no_account = false;		
	protected $db = null;	
	protected $_extra = false;		
	protected $_is_api = false;	

	//
	// Construct.
	//
	public function __construct()
	{	
		// Get the table.
		if(empty($this->table))
		{
			$table = explode('\\', get_called_class());
			$this->table = end($table);						
		}
		
		// Set table prefix
		if(is_null($this->table_prefix))
		{
			$this->table_prefix = $this->table;
		}
			
		// Setup the database connection.
		$this->_setup_query();
	}
	
	// ------------------------ Setters ------------------------------ //

	//
	// Set API call.
	//
	public function set_api($action = true)
	{
		$this->_is_api = $action;
	 	return $this;		
	}	
	
	//
	// Set join
	//
	public function set_join($table, $left, $right)
	{
		$this->db->join($table, $left, '=', $right);
	 	return $this;		
	}		
	
	//
	// Make it so it does not load the other models.
	//
	public function set_no_extra()
	{
		$this->_extra = false;
	 	return $this;		
	}
	
	//
	// We want all the extra data.
	//
	public function set_extra()
	{
		$this->_extra = true;
	 	return $this;		
	}
 
 	//
 	// Set since a particular date.
 	//
 	public function set_since($timestamp)
 	{
	 	$stamp = date('Y-m-d H:i:s', strtotime($timestamp));
	 	$this->db->where($this->table_prefix . 'UpdatedAt', '>=', $stamp);
	 	return $this;
 	}

	//
	// Set limit
	//
	public function set_limit($limit)
	{	
		$this->db->take($limit);
	 	return $this;		
	}	
	
	//
	// Set offset
	//
	public function set_offset($offset)
	{
		$this->db->skip($offset);
	 	return $this;
	}	
	
	//
	// Set order
	//
	public function set_order($order, $sort = 'desc')
	{
		$this->db->orderBy($order, $sort);
	 	return $this;		
	}	
	
	//
	// Set group
	//
	public function set_group($group)
	{
		$this->db->groupBy($group);
	 	return $this;		
	}	
	
	//
	// Set Column.
	//
	public function set_col($key, $value, $action = '=')
	{
		$this->db->where($key, $action, $value);
	 	return $this;		
	}
	
	//
	// Set Column OR.
	//
	public function set_or_col($key, $value)
	{
		$this->db->orWhere($key, '=', $value);
	 	return $this;		
	}
	
	//
	// Set Or Where In
	//
	public function set_or_where_in($col, $list)
	{
		$this->db->orWhereIn($col, $list);
	 	return $this;		
	}
	
	//
	// Set Where In
	//
	public function set_where_in($col, $list)
	{
		$this->db->whereIn($col, $list);
	 	return $this;		
	}	
	
	//
	// Set Columns to select.
	//
	public function set_select($selects)
	{
		$this->db->select($selects);
	 	return $this;		
	}	
	
	//
	// Set search.
	//
	public function set_search($str)
	{
		// Place holder we should override this.
	 	return $this;
	}
	
	// ------------------------ Getters ------------------------------ //	
	
	//
	// Get the export columns.
	//
	public function get_export_cols()
	{
		return $this->export_cols;
	}
	
	// ------------------------ Actions ------------------------------ //	
	
	//
	// Export Data. We use this when we are going to dump an entire table or something.
	// We do not do not call format_get. We do not apply any joins. Just the raw data.
	// 
	public function export()
	{
		$data = [];	
	
		// Return only selective data.
		if(count($this->export_cols))
		{
			$this->db->select($this->export_cols);
		}
	
		// Query
		$data = $this->db->get();
		
		// Convert to an array because we like arrays better.
		$data = $this->_obj_to_array($data);
		
		return $data;
	}
	
	// 
	// Get the first result
	//
	public function first()
	{
  	$this->set_limit(1);
  	$d = $this->get();
  	return (isset($d[0])) ? $d[0] : false;
	}
	
	//
	// Get...
	// 
	public function get()
	{	
		$data = [];
		
		// Do we have joins?
		if(! is_null($this->joins))
		{
			foreach($this->joins AS $key => $row)
			{
				$this->set_join($row['table'], $row['left'], $row['right']);
			}
		}		
		
		// Set the account.
		if(Auth::user()->AccountId && (! $this->no_account))
		{
			$this->db->where($this->table_prefix . $this->table_account_col, '=', Auth::user()->AccountId);
		}			
		
		// Query
		$data = $this->db->get();
		
		// Convert to an array because we like arrays better.
		$data = $this->_obj_to_array($data);
		
		// Reset the query.
		$this->_setup_query();		
		
		// An option formatting function call.
		if(method_exists($this, '_format_get'))
		{	
			// Loop through data and format.
			foreach($data AS $key => $row)
			{
				$this->_format_get($data[$key]);
			}
		}
		
		return $data;
	} 	
	
	//
	// Get by id.
	// 
	public function get_by_id($id)
	{
		$this->set_col($this->table_prefix . $this->table_id_col, $id);
		$d = $this->get();
		$data = (isset($d[0])) ? (array) $d[0] : false;				
		return $data;
	}	
	
	//
	// Insert.
	//
	public function insert($data)
	{	
		// Set the created and and updated fields
		$data[$this->table_prefix  . 'UpdatedAt'] = date('Y-m-d G:i:s');
		$data[$this->table_prefix . 'CreatedAt'] = date('Y-m-d G:i:s');
		$data['created_at'] = date('Y-m-d G:i:s');
		$data['updated_at'] = date('Y-m-d G:i:s');		
	
		// Set the account.
		if(Me::get_account_id() && (! $this->no_account))
		{
			$data[$this->table_prefix . $this->table_account_col] = Me::get_account_id();	
		}
		
 		// Insert the data / clear the query and return the ID.
 		$this->db->insert($this->_set_data($data));
 		$id = DB::connection($this->connection)->getPdo()->lastInsertId();
 		
		// Reset the query.
		$this->_setup_query();	 		

 		return $id;
	}	
	
	//
	// Update.
	//
	public function update($data, $id)
	{	
		// Set the created and and updated fields
		$data[$this->table_prefix  . 'UpdatedAt'] = date('Y-m-d G:i:s');
		$data['updated_at'] = date('Y-m-d G:i:s');	
	
		$this->set_col($this->table_prefix . $this->table_id_col, $id);
	
		// Set the account.
		if(Me::get_account_id() && (! $this->no_account))
		{
			$data[$this->table_prefix . $this->table_account_col] = Me::get_account_id();	
		}	
	
		$rt = $this->db->update($this->_set_data($data));
		
		// Reset the query.
		$this->_setup_query();		
				
		return $rt;
	}		
	
	//
	// Delete by id.
	//
	public function delete_by_id($id)
	{
		// Set the account.
		if(Me::get_account_id() && (! $this->no_account))
		{
			$this->db->where($this->table_prefix . $this->table_account_col, '=', Me::get_account_id());
		}	  	
  	
		$this->set_col($this->table_prefix . $this->table_id_col, $id);
 		$this->db->delete();
 		
		// Reset the query.
		$this->_setup_query();		 		
 		
 		return true;
	}	
	
  //
  // Delete All
  //
  public function delete_all()
  {
		// Set the account.
		if(Me::get_account_id() && (! $this->no_account))
		{
			$this->db->where($this->table_prefix . $this->table_account_col, '=', Me::get_account_id());
		}	    
    
    // Get data.
    $data = $this->get();
    
    foreach($data AS $key => $row)
    {
      $this->delete_by_id($row[$this->table_prefix . $this->table_id_col]);
    }

		// Reset the query.
		$this->_setup_query();		 		
 		
 		return true;
	}	    
	
	//
	// Get count.
	//
	public function get_count()
	{	
		// Do we have joins?
		if(! is_null($this->joins))
		{
			foreach($this->joins AS $key => $row)
			{
				$this->set_join($row['table'], $row['left'], $row['right']);
			}
		}
		
		// Get count.
		$r = $this->db->count();
		
		// Reset the query.
		$this->_setup_query();		
		
		return $r;
	}	
	
	//
	// Get the total of all rows in this table.
	//
	public function get_total()
	{
		// Set the account.
		if(Me::get_account_id() && (! $this->no_account))
		{
			$this->db->where($this->table_prefix . $this->table_account_col, '=', Me::get_account_id());
		}		
	
		return $this->db->count();
	}
	
	// ----------------- Helper Function  -------------- //
		
	//
	// Setup the connection. We need to do this after every query.
	//
	private function _setup_query()
	{
		$this->db = DB::connection($this->connection)->table($this->table);		
	}
		
 	//
 	// Convert the object the database returns to an array.
 	// Yes, PDO can return arrays, but Laravel really counts
 	// on objects instead of arrays.
 	//
 	private function _obj_to_array($data)
 	{
	 	if(is_array($data) || is_object($data))
	 	{
		 	$result = [];
		 	foreach($data as $key => $value)
		 	{
			 	$result[$key] = $this->_obj_to_array($value);
			}

			return $result;
		}
    
		return $data;
	}
	
 	//
 	// This will take the post data and filter out the non table cols.
 	//
	private function _set_data($data)
 	{
 		$q = array();
 		$fields = DB::connection($this->connection)->select('SHOW COLUMNS FROM ' . $this->table);
 		
 		foreach($fields AS $key => $row)
 		{ 
 			if(isset($data[$row->Field])) 
 			{
 				$q[$row->Field] = $data[$row->Field];
 			}
 		}
 		
 		return $q;
 	}	
	
	//
	// Get last query.
	//
	public function get_last_query()
	{
		$q = DB::getQueryLog();
		return end($q);
	}	
}

/* End File */