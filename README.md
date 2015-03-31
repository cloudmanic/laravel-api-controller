laravel-api-controller
======================

A special controller class for API responses the Cloudmanic Labs way.


Sample Controller (Laravel 4.x)
======================

```
<?php

class Products extends \Cloudmanic\LaravelApi\Controller
{
	public $validation_create = [];
	public $validation_update = [];
}
```

Sample Controller (Laravel 5.x)
======================

```
<?php 

namespace App\Http\Controllers\Api\V1;
	
class Products extends \App\Http\Controllers\Controller
{
	use \Cloudmanic\LaravelApi\Traits\Controller;
	
	public $validation_create = [];
	public $validation_update = [];	
}

/* End File */
```

Sample Model
======================

```
<?php

namespace Models;

class Products extends \Cloudmanic\LaravelApi\Model
{

}
```
