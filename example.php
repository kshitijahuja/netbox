<?php

/**
* Description: Netbox Example
* Author: Kshitij Ahuja
* Version: 1.0.0
* Author URI: http://academicdatasolutions.com
* Email: help@academicdatasolutions.com
**/

require("netbox.lib.php");

$netbox = new Netbox();

// this is login auth - we also have Mac authentication option
$netbox->setApiBaseUri("your netbox uri "); //example.com from the uri like https://example.com/goforms/nbapi
$response = $netbox->login("your username", "your password"); 
print_r($response);
print_r($netbox);

exit;
// operations
$personsArr = array("person_id" => "23", "first_name" => "Kshitij", "last_name" => "Test"); # manual override
// $response = $netbox->addPerson($personToAdd);

$response = $netbox->getPerson($personsArr);

print_r($response);

//logout
// $response = $netbox->logout();
// print_r($response);
// print_r($netbox);


?>