laravel-api-controller
======================

A special controller class for API responses the Cloudmanic Labs way.

Version 1.0 for Laravel 4.x

Version 2.0 for Laravel 5.x


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

Sample Model
======================

```
<?php

namespace Models;

class Products extends \Cloudmanic\LaravelApi\Model
{

}
```

Helper Functions
======================

Use this call to return a list of ids that have been deleted.

```Route::post('system/get_deleted', 'Cloudmanic\LaravelApi\Helper@get_deleted');``` 