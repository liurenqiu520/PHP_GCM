<?php

namespace Net\Http;

class Get extends Request {
	public function __construct($host, $path='/') {
        parent::__construct($host, $path, Method::GET);
    }
}

