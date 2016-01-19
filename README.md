laravel-api-controller
======================

A special controller class for API responses the Cloudmanic Labs way.

Version 1.0 for Laravel 4.x

Version 2.0 for Laravel 5.0 - 5.1

Version 2.0 for Laravel 5.2

Sample Controller (Laravel 5.x)
======================

```
<?php 

namespace App\Http\Controllers\Api\V1;
	
class Products extends \Cloudmanic\LaravelApi\Controller
{	
	public $validation_create = [];
	public $validation_update = [];	
}

/* End File */
```

Sample Model
======================

```
<?php

namespace App\Models;

class Products extends \Cloudmanic\LaravelApi\Model
{

}

/* End File */
```
