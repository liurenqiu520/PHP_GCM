<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Seikai
 * Date: 12/07/10
 * Time: 19:54
 * To change this template use File | Settings | File Templates.
 */
class ApnsConstants
{
    const APNS_SERVER_HOST = 'gateway.sandbox.push.apple.com';

    const APNS_SERVER_PORT = 2195;

    /** @var integer Default connect retry interval in micro seconds. */
    const CONNECT_RETRY_INTERVAL = 1000000;

    /** @var integer Default socket select timeout in micro seconds. */
    const SOCKET_SELECT_TIMEOUT = 1000000;

    /** @var integer */
    const MAX_MESSAGE_BYTE = 256;
    /** @var integer APNSから*/
    const MAX_TOTAL_MESSAGE_BYTE = 5000;

    const APS_MESSAGE_ALERT = 'alert';

    const APS_MAX_ALERT_WIDTH = 20;

    const APS_MESSAGE_SOUND = 'sound';

    const APS_MESSAGE_BADGE = 'badge';

}
