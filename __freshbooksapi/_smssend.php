<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$db = new PDO("mysql:host=localhost;dbname=admin_freelancer", "freelancer_db", "t8Z6MxzvB?1bipjc");

$sql = "SELECT * FROM sms_queue WHERE sms_queue_status = 0";
$result = $db->query($sql);

$data = $result->fetchAll();



require_once(dirname(__DIR__) . '/ringcentral/_bootstrap.php');
use RingCentral\SDK\SDK;

$credentials = require(dirname(__DIR__) . '/ringcentral/_credentials.php');

// Create SDK instance
$rcsdk = new SDK($credentials['appKey'], $credentials['appSecret'], $credentials['server'], 'Demo', '1.0.0');
$platform = $rcsdk->platform();

// Authorize
try
{
    $platform->login($credentials['username'], $credentials['extension'], $credentials['password']);
}
catch (\RingCentral\SDK\Http\ApiException $e)
{

    // Getting error messages using PHP native interface
    print 'Expected HTTP Error: ' . $e->getMessage() . PHP_EOL;

    // In order to get Request and Response used to perform transaction:
    $apiResponse = $e->apiResponse();
    print_r($apiResponse->request()); 
    print_r($apiResponse->response());

    // Another way to get message, but keep in mind, that there could be no response if request has failed completely
    //print '  Message: ' . $e->apiResponse->response()->error() . PHP_EOL;
}

$prepared = $db->prepare("UPDATE sms_queue SET sms_queue_status=1, sms_queue_date_sent=NOW() WHERE sms_queue_id=?");
foreach($data as $d)
{
    $response = $platform
        ->post('/account/~/extension/~/sms', array(
            'from' => array('phoneNumber' => $credentials['smsNumber']),
            'to'   => array(
                array('phoneNumber' => $d['sms_queue_telephone']),
            ),
            'text' => 'Test from PHP',
        ));

    //print 'Sent SMS ' . $response->json()->uri . PHP_EOL;
   
	$prepared->execute(array($d['sms_queue_id']));
}

?>