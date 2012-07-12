<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Seikai
 * Date: 12/07/10
 * Time: 19:51
 * To change this template use File | Settings | File Templates.
 */
class ApnsSender
{
    /**
     * Maximum delay before a retry.
     * @var int
     */
    const MAX_BACKOFF_DELAY = 1024000;

    /**
     * Initial delay before first retry, without jitter.
     * @var int
     */
    const BACKOFF_INITIAL_DELAY = 1000;

    /** @var Logger */
    protected $logger;

    /** @var SocketStream */
    private $stream;

    /** @var CertificationManager */
    private $cert;

    /** @var int */
    private $sendByte = 0;

    /**
     * @param CertificationManager $cert
     */
    public function __construct(CertificationManager $cert) {
        $this->cert = $cert;
    }

    /**
     * @param string $logfile
     */
    public function setLogger($logfile) {
        $this->logger = Logger::getLogger($logfile);
    }

    public function log($message) {
        if($this->logger != null) {
            $this->logger->log($message);
        }
    }

    /**
     *
     * @param ApnsMessage $message
     * @param $registrationId
     * @param $retries
     * @param bool $isLast
     * @return bool
     * @throws Exception
     */
    public function send(ApnsMessage $message, $registrationId, $retries, $isLast = true)
    {
        /** @var $attempt int */
        $attempt = 0;
        /** @var $result GcmResult */
        $result = false;

        $backOff = self::BACKOFF_INITIAL_DELAY;

        do {

            $attempt++;

            $result = $this->sendNoRetry($message, $registrationId, $isLast);

            $tryAgain = ($result == null && $attempt <= $retries);

            if ($tryAgain) {
                $sleepTime = $backOff / 2 + mt_rand(0, $backOff);
                usleep($sleepTime);
                if (2 * $backOff < self::MAX_BACKOFF_DELAY) {
                    $backOff *= 2;
                }
            }

        } while ($tryAgain);

        if (!$result) {
            throw new Exception("Could not send message after " . $attempt ." attempts");
        }

        return $result;
    }

    /**
     * @param string $payloadJson
     * @param int $payloadSize
     * @param string $registrationId
     * @return string
     */
    public function createMessage($payloadJson, $payloadSize, $registrationId) {
        return chr(0)
            . pack('n', 32)
            . pack('H*', $registrationId)
            . pack('n', $payloadSize)
            . $payloadJson;
    }

    /**
     * Sends a message without retrying
     *
     * @param ApnsMessage $message
     * @param $registrationId
     * @param bool $isLast
     * @return GcmResult
     * @throws InvalidRequestException
     * @throws Exception
     */
    public function sendNoRetry(ApnsMessage $message, $registrationId, $isLast = true) {

        $payload    = array();
        $payloadAps = new ArrayObject();

        $messageData = $message->getData();

        foreach($messageData as $key => $value) {
            $payload[$key] = $value;
        }

        if($message->getAlert() !== NULL) {
            $alert = mb_strimwidth($message->getAlert(), 0, ApnsConstants::APS_MAX_ALERT_WIDTH);
            $this->addParameter($payloadAps, ApnsConstants::APS_MESSAGE_ALERT, $alert);
        }

        if($message->getBadge() !== NULL) {
            $this->addParameter($payloadAps, ApnsConstants::APS_MESSAGE_BADGE, $message->getBadge());
        }

        if($message->getSound() !== NULL) {
            $this->addParameter($payloadAps, ApnsConstants::APS_MESSAGE_SOUND, $message->getSound());
        }

        $payload['aps'] = $payloadAps->getArrayCopy();
        $payloadJson = json_encode($payload);
        $payloadSize = strlen($payloadJson);

        $request = $this->createMessage($payloadJson, $payloadSize, $registrationId);

        $this->openStream();

        $result = $this->stream->write($request);

        $this->sendByte += strlen($request);

        if ($result === false) {
            throw new InvalidRequestException('apns could not send message.');
        }

        if($isLast || $this->sendByte >= ApnsConstants::MAX_TOTAL_MESSAGE_BYTE) {
            $this->closeStream();
        }

        return true;
    }

    /**
     *
     */
    private function openStream() {

        if($this->stream === NULL) {
            $this->stream = new SocketStream(ApnsConstants::APNS_SERVER_HOST, ApnsConstants::APNS_SERVER_PORT, 'ssl');
        }

        if(!$this->stream->isConnected()){
            $this->stream->setCertification($this->cert);
            $this->stream->open(false);
        }
    }

    /**
     *
     */
    private function closeStream() {
        if($this->stream === NULL) return;
        if($this->stream->isConnected()){
            $this->stream->close();
        }
    }

    public function __destruct() {
        $this->closeStream();
    }

    /**
     * @param ArrayObject $payload
     * @param string $name
     * @param string|int $value
     */
    private function addParameter(ArrayObject $payload, $name, $value) {
        $payload->offsetSet(self::nonNull($name), self::nonNull($value));
    }

    /**
     * @static
     * @param $argument
     * @return mixed
     * @throws InvalidArgumentException
     */
    private static function nonNull($argument)
    {
        if ($argument === null) {
            throw new InvalidArgumentException("argument cannot be null");
        }

        return $argument;
    }

}
