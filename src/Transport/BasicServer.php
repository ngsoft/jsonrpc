<?php

namespace NGSOFT\JsonRPC\Transport;

use NGSOFT\JsonRPC\Interfaces\Transport;

class BasicServer implements Transport {

    /** {@inheritdoc} */
    public function receive(): string {
        return @file_get_contents('php://input') ?: "";
    }

    /** {@inheritdoc} */
    public function reply(string $data) {

        // header('Access-Control-Allow-Origin: *');
        header('Cache-Control: private, max-age=0, no-cache');
        header('Content-Type: application/rpc+json');
        header("Content-Length: " . strlen($data));
        print $data;
    }

}
