<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Seikai
 * Date: 12/07/06
 * Time: 16:52
 * To change this template use File | Settings | File Templates.
 */

require_once 'AutoLoader.php';

AutoLoader::addIncludePath(array(
	dirname(__FILE__) . '/Gcm',
	dirname(__FILE__) . '/Apns',
	dirname(__FILE__) . '/libs',
));

AutoLoader::registerAutoLoad();

/*
$cert = new CertificationManager(
    new CertificationP12('pkcs12file.p12', 'pass')
);

$deviceRegistId = '__id__';

$sender = new ApnsSender($cert);

$builder = new ApnsMessageBuilder();
$builder->alert('test');
$builder->badge(1);
$builder->sound('default');
$message = $builder->build();

$sender->send($message, $deviceRegistId, 1);
*/


$API_KEY = '__YOUR_API_KEY__';

$deviceRegistId = '__your_regist_id__';

try {
    
    $sender = new GcmSender($API_KEY);

    $builder = new GcmMessageBuilder();
    $builder->addData('hello1', 'world1');
    $builder->addData('hello2', 'world2');
    
    $message = $builder->build();
    
    $result = $sender->send($message, $deviceRegistId, 3);
    
} catch (Exception $e) {
    
    Logger::getLogger(GcmConstants::LOG_FILE)->put( 'Exception : ' . $e->getMessage() );
    
}

Logger::getLogger(GcmConstants::LOG_FILE)->flush();


