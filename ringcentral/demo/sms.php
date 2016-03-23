<?php

require_once(__DIR__ . '/_bootstrap.php');

use RingCentral\SDK\SDK;

$credentials = require(__DIR__ . '/_credentials.php');

// Create SDK instance

$rcsdk = new SDK($credentials['appKey'], $credentials['appSecret'], $credentials['server'], 'Demo', '1.0.0');

$platform = $rcsdk->platform();

// Authorize
try {
    $platform->login($credentials['username'], $credentials['extension'], $credentials['password']);
} catch (\RingCentral\SDK\Http\ApiException $e) {

    // Getting error messages using PHP native interface
    print 'Expected HTTP Error: ' . $e->getMessage() . PHP_EOL;

    // In order to get Request and Response used to perform transaction:
    $apiResponse = $e->apiResponse();
    print_r($apiResponse->request()); 
    print_r($apiResponse->response());

    // Another way to get message, but keep in mind, that there could be no response if request has failed completely
    //print '  Message: ' . $e->apiResponse->response()->error() . PHP_EOL;

}


// Find SMS-enabled phone number that belongs to extension

$phoneNumbers = $platform->get('/account/~/extension/~/phone-number', array('perPage' => 'max'))->json()->records;

$smsNumber = null;

//print_r($phoneNumbers); exit;

foreach ($phoneNumbers as $phoneNumber) {

    if (in_array('SmsSender', $phoneNumber->features)) {

        $smsNumber = $phoneNumber->phoneNumber;

        break;

    }

}

print 'SMS Phone Number: ' . $smsNumber . PHP_EOL;

// Send SMS

if ($smsNumber) {

    $response = $platform
        ->post('/account/~/extension/~/sms', array(
            'from' => array('phoneNumber' => $smsNumber),
            'to'   => array(
                array('phoneNumber' => $credentials['mobileNumber']),
            ),
            'text' => 'Test from PHP',
        ));

    print 'Sent SMS ' . $response->json()->uri . PHP_EOL;

} else {

    print 'SMS cannot be sent: no SMS-enabled phone number found...' . PHP_EOL;

}

