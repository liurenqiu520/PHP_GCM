<?php

namespace Net\Http;

class Post extends Request {

    public function __construct($host, $path='/') {
        parent::__construct($host, $path, Method::POST);
        $this->addProperty(Header::CONNECTION, 'Close');
    }

    public function toString() {
        $payloadSize = $this->getPayloadSize();
        if($payloadSize > 0) {
            $this->addProperty(Header::CONTENT_LENGTH, $payloadSize);
        }
        return parent::toString();
    }
}
