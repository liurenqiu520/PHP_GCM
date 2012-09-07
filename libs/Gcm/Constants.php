<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Seikai
 * Date: 12/06/29
 * Time: 15:56
 * To change this template use File | Settings | File Templates.
 */
namespace Gcm;

class Constants
{
    /**
     * Payload Max Size
     */
    const MAX_PAYLOAD_SIZE = 4000;
    /**
     * Payload Max Size
     */
    const MAX_TARGET_DEVICE_COUNT = 1000;


    const LOG_FILE = '/tmp/gcm.log';
    /**
     * Endpoint for sending messages.
     */
    const GCM_SEND_ENDPOINT = 'https://android.googleapis.com/gcm/send';

    /**
     * HTTP parameter for registration id.
     */
    const PARAM_REGISTRATION_ID = 'registration_id';


    const PARAM_REGISTRATION_IDS = 'registration_ids';
    /**
     * HTTP parameter for collapse key.
     */
    const PARAM_COLLAPSE_KEY = 'collapse_key';

    /**
     * HTTP parameter for delaying the message delivery if the device is idle.
     */
    const PARAM_DELAY_WHILE_IDLE = 'delay_while_idle';

    /**
     * Prefix to HTTP parameter used to pass key-values in the message payload.
     */
    const PARAM_PAYLOAD_PREFIX = 'data.';

    /**
     * Prefix to HTTP parameter used to set the message time-to-live.
     */
    const PARAM_TIME_TO_LIVE = 'time_to_live';

    /**
     * Too many messages sent by the sender. Retry after a while.
     */
    const ERROR_QUOTA_EXCEEDED = 'QuotaExceeded';

    /**
     * Too many messages sent by the sender to a specific device.
     * Retry after a while.
     */
    const ERROR_DEVICE_QUOTA_EXCEEDED = 'DeviceQuotaExceeded';

    /**
     * Missing registration_id.
     * Sender should always add the registration_id to the request.
     */
    const ERROR_MISSING_REGISTRATION = 'MissingRegistration';

    /**
     * Bad registration_id. Sender should remove this registration_id.
     */
    const ERROR_INVALID_REGISTRATION = 'InvalidRegistration';

    /**
     * The sender_id contained in the registration_id does not match the
     * sender_id used to register with the GCM servers.
     */
    const ERROR_MISMATCH_SENDER_ID = 'MismatchSenderId';

    /**
     * The user has uninstalled the application or turned off notifications.
     * Sender should stop sending messages to this device and delete the
     * registration_id. The client needs to re-register with the GCM servers to
     * receive notifications again.
     */
    const ERROR_NOT_REGISTERED = 'NotRegistered';

    /**
     * The payload of the message is too big, see the limitations.
     * Reduce the size of the message.
     */
    const ERROR_MESSAGE_TOO_BIG = 'MessageTooBig';

    /**
     * Collapse key is required. Include collapse key in the request.
     */
    const ERROR_MISSING_COLLAPSE_KEY = 'MissingCollapseKey';

    /**
     * Used to indicate that a particular message could not be sent because
     * the GCM servers were not available. Used only on JSON requests, as in
     * plain text requests unavailability is indicated by a 503 response.
     */
    const ERROR_UNAVAILABLE = 'Unavailable';

    /**
     * Token returned by GCM when a message was successfully sent.
     */
    const TOKEN_MESSAGE_ID = 'id';

    /**
     * Token returned by GCM when the requested registration id has a canonical
     * value.
     */
    const TOKEN_CANONICAL_REG_ID = 'registration_id';

    /**
     * Token returned by GCM when there was an error sending a message.
     */
    const TOKEN_ERROR = 'Error';

    /**
     * JSON-only field representing the registration ids.
     */
    const JSON_REGISTRATION_IDS = 'registration_ids';

    /**
     * JSON-only field representing the payload data.
     */
    const JSON_PAYLOAD = 'data';

    /**
     * JSON-only field representing the number of successful messages.
     */
    const JSON_SUCCESS = 'success';

    /**
     * JSON-only field representing the number of failed messages.
     */
    const JSON_FAILURE = 'failure';

    /**
     * JSON-only field representing the number of messages with a canonical
     * registration id.
     */
    const JSON_CANONICAL_IDS = 'canonical_ids';

    /**
     * JSON-only field representing the id of the multicast request.
     */
    const JSON_MULTICAST_ID = 'multicast_id';

    /**
     * JSON-only field representing the result of each individual request.
     */
    const JSON_RESULTS = 'results';

    /**
     * JSON-only field representing the error field of an individual request.
     */
    const JSON_ERROR = 'error';

    /**
     * JSON-only field sent by GCM when a message was successfully sent.
     */
    const JSON_MESSAGE_ID = 'message_id';

}
