<?php
ini_set('display_errors', 1);
require_once('../../ringcentral/demo/_bootstrap.php');

use RingCentral\SDK\SDK;

$credentials = require('../../ringcentral/demo/_credentials.php');

// Create SDK instance

$rcsdk = new SDK($credentials['appKey'], $credentials['appSecret'], $credentials['server'], 'Demo', '1.0.0');

$platform = $rcsdk->platform();

// Authorize

$platform->login($credentials['username'], $credentials['extension'], $credentials['password']);

// Make a call

$response = $platform->post('/account/~/extension/~/ringout', array(
    'from' => array('phoneNumber' => '15551112233'),
    'to'   => array('phoneNumber' => $credentials['mobileNumber'])
));

$json = $response->json();

$lastStatus = $json->status->callStatus;

// Poll for call status updates

while ($lastStatus == 'InProgress') {

    $current = $platform->get($json->uri);
    $currentJson = $current->json();
    $lastStatus = $currentJson->status->callStatus;
    print 'Status: ' . json_encode($currentJson->status) . PHP_EOL;

    sleep(2);

}

print 'Done.' . PHP_EOL;
