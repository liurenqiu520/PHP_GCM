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

    public function create($type) {
        $class = ucfirst($type);
        if(class_exists($type, true)) {
            return new $class();
        }
    }
}
