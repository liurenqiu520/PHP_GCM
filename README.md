GcmPHP
=====================

Send message by Google Cloud Messaging for Android

Example
----------

PHP Source:

```php:
require_once 'libs/AutoLoader.php';

AutoLoader::addIncludePath(array(
	dirname(__FILE__) . '/libs',
));

AutoLoader::register();

$API_KEY = 'Your_API_Key';

$builder = new \Gcm\MessageBuilder();
$builder->addData('hello1', 'world1');
$builder->addData('hello2', 'world2');

$message = $builder->build();

$deviceRegistIds = array(
    'Your_Reg_ID1',
    'Your_Reg_ID2',
    'Your_Reg_ID3',
    'Your_Reg_ID4',
);

$sender = new \Gcm\Sender($API_KEY);

//Single
$result = $sender->send($message, $deviceRegistIds[0], 3);
var_dump($result);

//Multi
$result = $sender->sendMulti($message, $deviceRegistIds, 3);
var_dump($result);
```

