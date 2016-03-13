<?php 
//
// Company: Cloudmanic Labs, LLC
// Website: http://cloudmanic.com
// Date: 3/6/2016
//

namespace Cloudmanic\LaravelApi;

use DB;
use App;
use Crypt;
use Illuminate\Contracts\Auth\Guard;

class Auth
{  
  //
  // Logout.
  //
  public static function logout()
  {
    // Flush session
    session()->flush();
    
    // Delete all sessions for this user.
    DB::table('Sessions')->where('user', Me::get('UsersId'))->delete();
    
    return true;    
  }
  
  //
  // Attempt to log a user in.
  //
  public static function attempt($email, $password)
  {
    // See if we know this email address
    if(! $user = (array) DB::table('Users')->where('UsersEmail', $email)->first())
    {
      return false;
    }
    
    // Validate password.
    if(md5($password . $user['UsersSalt']) != $user['UsersPassword'])
    {
      return false;
    }
    
    // If we made it this far we assume they authed ok. Lets setup the session. 
    if(! static::login($user['UsersId']))
    {
      return false;
    }
    
    // Return happy.
    return true;   
  }
  
  //
  // Are we logged in?
  //
  public static function check()
  {    
    // Check to see if we have a session.
    if((! $user_id = session('LoggedIn')) && (! $user_id = static::get_by_access_token()))
    {      
      return false;
    }
    
    // If we made it this far we assume they authed ok. Lets setup the session. 
    if(! static::login($user_id))
    {
      return false;
    } 
     
    return true;
  }
  
  //
  // Log a user in.
  //
  public static function login($user_id, $account_id = null)
  {
    $accounts = [];  
    
    // Get the user.
    if(! $user = (array) DB::table('Users')->where('UsersId', $user_id)->first())
    {
      return false;
    }    
    
    // Get accounts of this user.
    $accts = DB::table('AcctUsersLu')->join('Accounts', 'AcctUsersLuAcctId', '=', 'AccountsId')->where('AcctUsersLuUserId', $user_id)->orderBy('AccountsDisplayName', 'asc')->get();
    if(! $accts)
    {
      return false;      
    }
    
    // Turn accounts into arrays
    foreach($accts AS $key => $row)
    {
      $accounts[] = (array) $row;
    }
    
    // Add the accounts to the user obj.
    $user['Accounts'] = $accounts;
    
    // Set the Me var
    Me::set($user);
      
    // Set the user id session
    session([ 'LoggedIn' => $user['UsersId'] ]);
    
    // Do we have an account id to work from?
    if(is_null($account_id))
    {
      if(request()->input('account_id'))
      {
        $account_id = request()->input('account_id');
      } else if(session()->has('AccountsId'))
      {
        $account_id = session()->has('AccountsId');      
      }
    }
    
    // Set the default account id.
    if(! is_null($account_id))
    {
      if(! $account = static::_validate_account_id($account_id, $accounts))
      {
        return false;
      } else
      {
        Me::set_account($account);
        session([ 'AccountsId' => $account_id ]);
      }
    } else
    {
      Me::set_account($accounts[0]);
      session([ 'AccountsId' => $accounts[0]['AccountsId'] ]); 
    }
    
    // Update session table with user id. (we use our own custom field to not muck with laravel)
    DB::table(config('session.table'))->where('id', session()->getId())->update([ 'user' => $user_id ]);

    // Return happy
    return true;
  }
  
  //
  // Get user by access token
  //
  public static function get_by_access_token()
  {
    if(! $access_token = request()->input('access_token'))
    {
      return false;
    }    
        
    // Need an account id to go much further
    if(! $account_id = request()->input('account_id'))
    {
      return false;
    }
    
    // Default value
    $token = null;
    
    // Find all possible access tokens for this accountid.
    $accts = DB::table('AcctUsersLu')->select('AcctUsersLuUserId')->where('AcctUsersLuAcctId', $account_id)->get();
     
    // Look for this id.
    foreach($accts AS $key => $row)
    {
      $tks = DB::table('AccessTokens')->where('AccessTokensUserId', $row->AcctUsersLuUserId)->get();
      
      foreach($tks AS $key2 => $row2)
      {
        if(Crypt::decrypt($row2->AccessTokensToken) == $access_token)
        {
          $token = (array) $row2;
        }
      }
    }    
    
    // Get the user. 
    if(! is_null($token))
    {
      $user = (array) DB::table('Users')->where('UsersId', $token['AccessTokensUserId'])->first();    
      return $user['UsersId'];     
    } 
    
    // No luck.
    return false;    
  }
  
  // --------------- Private helper functions ------------------ //
  
  //
  // Validate account id.
  //
  private static function _validate_account_id($account_id, $accounts)
  {
    // Loop through and find this account.
    foreach($accounts AS $key => $row)
    {
      if($row['AccountsId'] == $account_id)
      {
        return $row;          
      }
    }
    
    return false;    
  }
}

/* End File */