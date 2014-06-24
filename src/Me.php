<?php 
//
// Company: Cloudmanic Labs, LLC
// Website: http://cloudmanic.com
// Date: 10/7/2012
//

namespace Cloudmanic\LaravelApi;

class Me
{
	private static $data = array();
	private static $account = array();

	//
	// Get one index in the data array. (Legacy, should be removed).
	//
	public static function val($key)
	{
		return (isset(self::$data[$key])) ? self::$data[$key] : '';
	}

	//
	// Get logged in user.
	//
	public static function get($key = null)
	{
		if(! is_null($key))
		{
			return (isset(self::$data[$key])) ? self::$data[$key] : '';
		}		
	
		return self::$data;
	}

	//
	// Set logged in user.
	//
	public static function set($data)
	{
		// Remove password hashes
		if(isset($data['UsersPassword']))
		{
			unset($data['UsersPassword']);
		}
	
		// Remove password salts
		if(isset($data['UsersSalt']))
		{
			unset($data['UsersSalt']);
		}
	
		self::$data = $data;
	}
	
	//
	// Set Account.
	//
	public static function set_account($data)
	{
		self::$account = $data;
	}
	
	//
	// Get account data.
	//
	public static function get_account($key = null)
	{
		if(is_null($key))
		{
			return self::$account;
		}
		
		return (isset(self::$account[$key])) ? self::$account[$key] : '';
	}
	
	//
	// Get my account id.
	//
	public static function get_account_id()
	{
		return (isset(self::$account['AccountsId'])) ? self::$account['AccountsId'] : 0;
	}
}

/* End File */