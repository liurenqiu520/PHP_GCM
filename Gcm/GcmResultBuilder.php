<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Seikai
 * Date: 12/07/06
 * Time: 16:34
 * To change this template use File | Settings | File Templates.
 */
class GcmResultBuilder {

    /** @var string */
    private $messageId = '';
    /** @var string */
    private $canonicalRegistrationId = '';
    /** @var string */
    private $errorCode = '';

    /**
     * @param string $value
     * @return GcmResultBuilder
     */
    public function canonicalRegistrationId($value) {
        $this->canonicalRegistrationId = $value;
        return $this;
    }

    /**
     * @param string $value
     * @return GcmResultBuilder
     */
    public function messageId($value) {
        $this->messageId = $value;
        return $this;
    }

    /**
     * @param string $value
     * @return GcmResultBuilder
     */
    public function errorCode($value) {
        $this->errorCode = $value;
        return $this;
    }

    /**
     * @return GcmResult
     */
    public function build() {
        return new GcmResult($this);
    }

    /**
     * @return string
     */
    public function getCanonicalRegistrationId()
    {
        return $this->canonicalRegistrationId;
    }

    /**
     * @return string
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @return string
     */
    public function getMessageId()
    {
        return $this->messageId;
    }
}
