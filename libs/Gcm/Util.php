<?php
/**
 * Created by JetBrains PhpStorm.
 * User: info
 * Date: 12/09/14
 * Time: 12:43
 * To change this template use File | Settings | File Templates.
 */
namespace Gcm;

class Util
{

    /**
     * @static
     * @param $argument
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public static function nonNull($argument)
    {
        if ($argument === null) {
            throw new \InvalidArgumentException("argument cannot be null");
        }
        return $argument;
    }


    /**
     * @static
     * @param array $json
     * @param $field
     * @return int|float
     * @throws \Exception
     */
    public static function getNumber(array $json, $field)
    {
        $value = $json[$field];
        if ($value === null) {
            throw new \Exception('Missing field: ' . $field);
        }

        if (!is_numeric($value)) {
            throw new \Exception('Field ' . $field .
                ' does not contain a number: ' . $value);
        }
        return $value;
    }



    /**
     * @static
     * @param \ArrayObject $jsonRequest
     * @param $key
     * @param $value
     */
    public static function setJsonField(\ArrayObject $jsonRequest, $key, $value)
    {
        if ($value != null) {
            $jsonRequest->offsetSet($key, $value);
        }
    }
}
