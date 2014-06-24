laravel-api-controller
======================

A special controller class for API responses the Cloudmanic Labs way.


Sample Controller
======================

```
<?php

class Products extends \Cloudmanic\LaravelApi\Controller
{
	public $rules_create = [];
	public $rules_update = [];
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
