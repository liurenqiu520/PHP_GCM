<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Seikai
 * Date: 12/06/29
 * Time: 19:50
 * To change this template use File | Settings | File Templates.
 */
class GcmResult
{
    /** @var string */
    private $messageId = '';
    /** @var string */
    private $canonicalRegistrationId = '';
    /** @var string */
    private $errorCode = '';

    /**
     * @param GcmResultBuilder $builder
     *
     */
    public function __construct(GcmResultBuilder $builder) {
        $this->canonicalRegistrationId = $builder->getCanonicalRegistrationId();
        $this->messageId = $builder->getMessageId();
        $this->errorCode = $builder->getErrorCode();
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

    public function __toString() {
        $string = '[';

        if ($this->messageId != null) {
            $string .= ' messageId=';
            $string .= $this->messageId;
        }

        if ($this->canonicalRegistrationId != null) {
            $string .= ' canonicalRegistrationId=';
            $string .= $this->canonicalRegistrationId;
        }

        if ($this->errorCode != null) {
            $string .= ' errorCode=';
            $string .= $this->errorCode;
        }

        $string .= ' ]';
        return $string;
    }

}

