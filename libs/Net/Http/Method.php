<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Seikai
 * Date: 12/09/03
 * Time: 22:09
 * To change this template use File | Settings | File Templates.
 */

namespace Net\Http;

class Method {
    const GET    = 'GET';
    const POST   = 'POST';
    const PUT    = 'PUT';
    const DELETE = 'DELETE';

    /**
     * @static
     * @param $type
     * @param $host
     * @param string $path
     * @return Request
     * @throws \InvalidArgumentException
     */
    public static function create($type, $host, $path='/') {
        $class = 'Net\\Http\\' . ucfirst(strtolower($type));
        if(class_exists($class, true)) {
            $request = new $class($host, $path);
            if($request instanceof Request) {
                return $request;
            }
            else {
                throw new \InvalidArgumentException('$type is invalid value.[' . $type . ']');
            }
        }
    }
}
