<?php
//
// Company: Cloudmanic Labs, LLC
// Website: http://cloudmanic.com
// Date: 3/6/2016
//

namespace Cloudmanic\LaravelApi;

use Closure;

class AuthMiddleware
{
  //
  // Handle an incoming request.
  //
  // @param  \Illuminate\Http\Request  $request
  // @param  \Closure  $next
  // @param  string|null  $guard
  // @return mixed
  //
  public function handle($request, Closure $next, $guard = null)
  {      
    // Check to see if we are logged in.
    if(! Auth::check())
    {
      if($request->ajax()) 
      {
        return response('Unauthorized.', 401);
      } else 
      {
        // No need to redirect on an API call that does not auth.
        if($request->input('access_token'))
        {
          return response('Unauthorized.', 401);
        }
        
        return redirect()->guest('login');
      }        
    }
    
    return $next($request);
  }
}

/* End File */