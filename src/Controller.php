<?php

namespace Cloudmanic\LaravelApi;

use \App;
use \Cache;
use \Input;
use \Request;
use \Response;
use \Validator;

class Controller extends \Controller 
{
	public $cached = false;
	public $cached_time = 60;
	public $model = null;
	public $model_name = '';
	public $model_namespace = 'Models\\';
	public $accept_update = null;
	public $accept_insert = null;	
	public $validation_create = [];
	public $validation_update = [];
	public $validation_message = [];
	public $validation_field_replace = [];		
	
	//
	// Construct.
	//
	public function __construct()
	{		
		// Guess the model.
		if(empty($this->model_name))
		{
			$tmp = explode('\\', get_called_class()); 	
			$this->model_name = $this->model_namespace . end($tmp);
		
			// Do nothing if this is not a real class.	
			if(class_exists($this->model_name))
			{
			  $this->model = App::make($this->model_name);
			}
		} else
		{
			$this->model = App::make($this->model_name);
		}
	}

	//
	// Get function call.
	//
	public function get()
	{
		// Request hash.
		$hash = 'api-' . md5(Request::getUri());
	
		// Is this a cached response?	
		if($this->cached)
		{
			if($data = Cache::get($hash))
			{
				return $this->api_response($data);
			}
		}	
			
		// Tell the model that this is an api call.
		if(method_exists($this->model, 'set_api'))
		{
			$this->model->set_api(true);	
		}
		
		// Setup query. Apply any filters we might have passed in.
		$this->_setup_query();		
		
		// Load model and run the query.			
		$data = $this->model->get();
		
		// Store the cache of this response
		if($this->cached)
		{
			Cache::put($hash, $data, $this->cached_time);
		}
		
		return $this->api_response($data);
	}
	
	//
	// Get by id. 
	// Returns status 0 if there was no data found.
	//
	public function id($_id)
	{
		// Request hash.
		$hash = 'api-' . md5(Request::getUri());
		
		// Is this a cached response?	
		if($this->cached)
		{
			if($data = Cache::get($hash))
			{
				return $this->api_response($data);
			}
		}
	
		// Tell the model that this is an api call.
		if(method_exists($this->model, 'set_api'))
		{
			$this->model->set_api(true);	
		}
		
		// Run query.
		if($data = $this->model->get_by_id($_id))
		{	
			// Store the cache of this response
			if($this->cached)
			{
				Cache::put($hash, $data, $this->cached_time);
			}
		
			return $this->api_response($data);
		} else
		{
			return $this->api_response([], 0, [ 'system' => 'Entry not found.' ]);
		}
	}	
	
	//
	// Insert.
	//
	public function create()
	{		
		// Tell the model that this is an api call.
		if(method_exists($this->model, 'set_api'))
		{
			$this->model->set_api(true);	
		}	
			
		// Validate this request. 
		if($rt = $this->validate_request('create'))
		{
			return $rt;
		}

		// A hook before we go any further.
		if(method_exists($this, '_before_create_or_update'))
		{
		  $this->_before_create_or_update();
		}
		
		// A hook before we go any further.
		if(method_exists($this, '_before_create'))
		{
		  $this->_before_create();
		}	
		
		// Set the input that we accept. 
		if($this->accept_insert)
		{
			$input = Input::only(implode(',', $this->accept_insert));
		} else
		{
			$input = Input::get();
		}
		
		// Load model and insert data.
		$this->model->set_api(true);
		$data['Id'] = $this->model->insert($input);	
		
		// A hook before we go any further.
		if(method_exists($this, '_after_insert'))
		{
		  $this->_after_insert();
		}
		
		return $this->api_response($data);
	}
	
	//
	// Update.
	//
	public function update($id)
	{				
		// Tell the model that this is an api call.
		if(method_exists($this->model, 'set_api'))
		{
			$this->model->set_api(true);	
		}	
	
		// A hook before we go any further.
		if(method_exists($this, '_before_validate'))
		{
		  $this->_before_validate();
		}	
	
		// Validate this request. 
		if($rt = $this->validate_request('update'))
		{
			return $rt;
		}

		// A hook before we go any further.
		if(method_exists($this, '_before_create_or_update'))
		{
		  $this->_before_create_or_update();
		}
		
		// A hook before we go any further.
		if(method_exists($this, '_before_update'))
		{
		  $this->_before_create();
		}	
		
		// Set the input that we accept. 
		if($this->accept_update)
		{
			$input = Input::only(implode(',', $this->accept_update));
		} else
		{
			$input = Input::get();
		}
		
		// Load model and insert data.
		$this->model->set_api(true);
		$this->model->update($input, $id);	
		$data['Id'] = $id;
		
		// A hook before we go any further.
		if(method_exists($this, '_after_update'))
		{
		  $this->_after_insert();
		}
		
		return $this->api_response($data);
	}	
	
	//
	// Delete a record by id.
	//
	public function delete($_id = null)
	{	
		// Tell the model that this is an api call.
		if(method_exists($this->model, 'set_api'))
		{
			$this->model->set_api(true);	
		}	
	
		// So we can support posts as well.
		if(is_null($_id))
		{
			$_id = Input::get('Id');
		}

		$this->model->delete_by_id($_id);
		return $this->api_response();
	}	
	
	// ----------------- Helper Functions ----------------- //
	
	//
	// Setup the query. Apply any filters we might have passed in.
	//
	private function _setup_query($limit = true)
	{	
		// A hook so we can add more query attributes.
		if(method_exists($this, '_before_setup_query'))
		{
		  $this->_before_setup_query();
		}
	
		// Setup column selectors
		$cols = array_keys(Input::get());
		foreach($cols AS $key => $row)
		{
			if(preg_match('/^(col_)/', $row))
			{
				if(Input::get($row) || (Input::get($row) == '0'))
				{
					$col = str_replace('col_', '', $row);
					
					if(method_exists($this->model, 'set_col'))
					{
						$this->model->set_col($col, Input::get($row));
					}
				}
			}
		}
	
		// Order by...
		if(Input::get('order'))
		{
			if(Input::get('sort'))
			{
				if(method_exists($this->model, 'set_order'))
				{
					$this->model->set_order(Input::get('order'), Input::get('sort'));
				}
			} else
			{
				if(method_exists($this->model, 'set_order'))
				{
					$this->model->set_order(Input::get('order'));				
				}
			}
		}
		
		// Select columns...
		if(Input::get('select') && (method_exists($this->model, 'set_select')))
		{
			$this->model->set_select(explode(',', Input::get('select')));
		}
		
		// Set limit...
		if($limit && Input::get('limit') && (method_exists($this->model, 'set_limit')))
		{
			$this->model->set_limit(Input::get('limit'));
		}
		
		// Set offset...
		if($limit && Input::get('offset') && Input::get('limit') && (method_exists($this->model, 'set_offset')))
		{
			$this->model->set_offset(Input::get('offset'));
		}
		
		// Set search....
		if(Input::get('search') && (method_exists($this->model, 'set_search')))
		{
			$this->model->set_search(Input::get('search'));
		}
		
		// Set extra
		if(Input::get('extra') && (method_exists($this->model, 'set_extra')))
		{
			$this->model->set_extra(Input::get('extra'));
		}
	}	
	
	//
	// Validate requests.
	//
	public function validate_request($type)
	{				
		// A hook before we go any further.
		if(method_exists($this, '_before_validation'))
		{
		  $this->_before_validation();
		}
		
		// Set rules.
		if($type == 'create')
		{
		  $rules = $this->validation_create;
		} else
		{
		  $rules = $this->validation_update;				
		}		
		
		// If we have rules we validate.
		if(is_array($rules) && (count($rules > 0)))
		{
			$validation = Validator::make(Input::all(), $rules, $this->validation_message);
		
			if($validation->fails())
			{
				$errors = [];
			  $messages = $validation->messages();
			  
				foreach(Input::all() AS $key => $row)
				{
					if($messages->has($key))
					{
						$errors[$key] = $messages->first($key);
					}
				}
			  
			  return $this->api_response(null, 0, $errors);
			}
		}
		
		// We consider true a state with errors.
		return false;
	}	
	
	//
	// Return a response based on the get "format" param.
	//	
	public function api_response($data = null, $status = 1, $errors = null)
	{
		// Setup the return array
		$rt = [
			'status' => $status,
			'data' => (! is_null($data)) ? $data : [],
			'limit' => Input::get('limit'),
			'offset' => Input::get('offset'),
			'count' => (! is_null($data)) ? count($data) : 0,
			'total' => ($this->model) ? $this->model->get_total() : 0,
			'filtered' => 0,
			'errors' => [],
			'hash' => md5(json_encode($data))
		];
		
		// If we are doing a search or some sort of filter with a limit we need to figure out how 
		// the total number of results without the limit or offset.
		if(Input::get('limit'))
		{
			$this->_setup_query(false);
			$rt['filtered'] = $this->model->get_total();
		}
		
		// Set errors.
		if(! is_null($errors))
		{
			// Format the errors
			foreach($errors AS $key => $row)
			{
				// Replace text in an error message.
				if(isset($this->validation_field_replace[$key]))
				{
				  $row = str_ireplace($key, $this->validation_field_replace[$key], $row);
				}			
			
				$rt['errors'][] = [ 'field' => $key, 'error' => $row ];
			}
		}		
		
		// Sometimes we just want to return just the hash of the data.
		if(Input::get('only_hash') && isset($rt['hash']))
		{				  
			$rt = [ 'status' => 1, 'hash' => $rt['hash'] ];
		}
		
		// Format the return in the output passed in.
		switch(Input::get('format'))
		{
			case 'php':
				return '<pre>' . print_r($rt, TRUE) . '</pre>';
			break;
			
			case 'jsonp':
				if(Input::get('callback'))
				{
				  return Input::get('callback') . '(' . json_encode($rt) . ')';
				}
		
				return 'callback(' . json_encode($rt) . ')';
			break;
			
			default:
				return Response::json($rt);
			break;
		}					
	}
}

/* End File */