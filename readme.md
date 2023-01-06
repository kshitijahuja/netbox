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
$response = $netbox->login("kahuja", "nmm3pEcU2SHL"); 
print_r($response);
print_r($netbox);

exit;
// operations
$personToAdd = array("person_id" => "23", "first_name" => "Kshitij", "last_name" => "Test"); # manual override
// $response = $netbox->addPerson($personToAdd);

$response = $netbox->getPerson($personToAdd);

print_r($response);

//logout
// $response = $netbox->logout();
// print_r($response);
// print_r($netbox);


?>
