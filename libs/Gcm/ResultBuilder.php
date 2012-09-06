<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Seikai
 * Date: 12/07/06
 * Time: 16:34
 * To change this template use File | Settings | File Templates.
 */
namespace Gcm;

class ResultBuilder
{

    /** @var string */
    private $messageId = '';
    /** @var string */
    private $canonicalRegistrationId = '';
    /** @var string */
    private $errorCode = '';

    /**
     * @param string $value
     * @return ResultBuilder
     */
    public function canonicalRegistrationId($value)
    {
        $this->canonicalRegistrationId = $value;
        return $this;
    }

    /**
     * @param string $value
     * @return ResultBuilder
     */
    public function messageId($value)
    {
        $this->messageId = $value;
        return $this;
    }

    /**
     * @param string $value
     * @return ResultBuilder
     */
    public function errorCode($value)
    {
        $this->errorCode = $value;
        return $this;
    }

    /**
     * @return Result
     */
    public function build()
    {
        return new Result($this);
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
