<?php

namespace Cloudmanic\LaravelApi;

use DB;
use App;
use Input;
use Config;
use Response;

class Helper extends \Controller
{
  //
  // We post in a list of table Ids. We return a list of ids that are 
  // no longer in the database. This is useful for figuring out what was deleted.
  //
  public function get_deleted()
  {
    $ids = Input::get('ids');
    $table = Input::get('table');
    
    // A way to test from the url
    if(Input::get('ids_string'))
    {
      $ids = explode(',', Input::get('ids_string'));
    }
    
    // Figure out what tables do not need accounts.
    $no_accounts = Config::get('site.no_accounts');
    
    // Query the table for ids
    if(in_array($table, $no_accounts))
    {
      $query = DB::table($table)->select($table . 'Id')->get(); 
    } else
    {      
      $query = DB::table($table)->select($table . 'Id')->where($table . 'AccountId', Me::get_account_id())->get();       
    }
    
    // Loop through and index the known ids.
    $known = [];
    foreach($query AS $key => $row)
    {
      $known[] = $row->ExercisesId;
    }
    
    // Figure out what is missing. 
    foreach($ids AS $key => $row)
    {
      if(in_array($row, $known))
      {
        unset($ids[$key]);
      }
    }
    
    // Resort the array
    sort($ids);
    
    // Return a list of ids that we do not know about. They must have been deleted.
    return $this->api_response($ids);
  }
  
	//
	// Return a response based on the get "format" param.
	//	
	public function api_response($data = null, $status = 1)
	{
		// Setup the return array
		$rt = [
			'status' => $status,
			'data' => (! is_null($data)) ? $data : [],
			'timestamp' => date('Y-m-d H:i:s')
		];
		

		// Format the return in the output passed in.
		switch(Input::get('format'))
		{
			case 'php':			
			case 'human':
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