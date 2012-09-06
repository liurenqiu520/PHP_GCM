<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Seikai
 * Date: 12/06/29
 * Time: 17:16
 * To change this template use File | Settings | File Templates.
 */
namespace Net\Http;

class InvalidRequestException extends \Exception
{
    /** @type int */
    private $status;

    /** @type string */
    private $description;

    public function __construct($description = '', $status = 0)
    {
        $this->status = $status;
        $this->description = $description;
        parent::__construct($description, $status);
    }

    private static function createMessage($status, $description = '')
    {
        $base = 'HTTP Status Code: ' . $status;
        if ($description != '') {
            $base .= '(' . $description . ')';
        }
        return $base;
    }

    /**
     * Gets the HTTP Status Code.
     */
    public function getHttpStatusCode()
    {
        return $this->status;
    }

    /**
     * Gets the error description.
     */
    public function getDescription()
    {
        return $this->description;
    }

}
