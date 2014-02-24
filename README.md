Maxoptra CakePHP 2.x Plugin
================

CakePHP 2.x Plugin to schedule deliveries and collections with Maxoptra via their REST API.

### Installation

Copy the `Maxoptra` folder from this repository to your `app/Plugin` directory so you end up with `app/Plugin/Maxoptra`.

If you do not already have all Plugins loaded, make sure you load this Plugin from your `Config/bootstrap.php` file as below.

```
CakePlugin::load('Maxoptra'); 
```

You can load all Plugins instead, with the following.

```
CakePlugin::loadAll(); 
```

### Requirements

* CakePHP 2.x
* Maxoptra Account/API Key

### Tests

Could use some XD

### Usage

```
$api_key = 'XXXXXXXXXXXXXXXXXXX'; // Maxoptra API Key
$this->Maxoptra = new Maxoptra($api_key);

$post_data = array(
	'orderReference' => 'MCM1234', 					// Your order reference
	'areaOfControl' => 'Manchester', 				// Area of Control in Maxoptra for this order
	'date' => '28/03/2014,						// dd/mm/yyyy
	'client' => array(
			'name' => 'Mad Capsule Media',			// Receipitent Company or Person name
			'contactPerson' => 'Bill Withers',		// Receipitent Person fullname
			'contactNumber' => '01171231234'		// Receipitent contact telephone number
			),
	'location' => array(
			'name' => 'Bill Withers',			// Receipitent name at location
			'address' => '123 Surrey Street BS2 9TE'	// Receipitent address, without country
			),
	'dropWindows' => array(
			'dropWindow' => array(
					'start' => '05/08/2014 08:00', // Drop window start
					'end' => '05/08/2014 15:00'    // Drop window end
					)
			),
	'priority' => 2, 						// Order priority
	'durationDrop' => '00:10',	 				// Drop duration MM:SS
	'capacity' => '100', 						// Shipment weight - optional
	'volume' => '200', 						// Shipment volume - optional
	'collection' => true, 						// Collection or Delivery true/false
	'additionalInstructions' => 'Beware of the dog!'		// Instructions to driver
	);

$this->Maxoptra->delivery($post_data);
```

