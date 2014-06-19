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
		
		// Load model and run the query.			
		$data = $this->model->get();
		
		// Store the cache of this response
		if($this->cached)
		{
			Cache::put($hash, $data, $this->cached_time);
		}
		
		return $this->api_response($data);
	}
	
	// ----------------- Helper Functions ----------------- //
	
	//
	// Return a response based on the get "format" param.
	//	
	public function api_response($data = null, $status = 1)
	{
		// Setup the return array
		$rt = [
			'status' => $status,
			'data' => (! is_null($data)) ? $data : [],
			'count' => (! is_null($data)) ? count($data) : 0,
			'errors' => [],
			'hash' => md5(json_encode($data))
		];
		
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