<?php

namespace NGSOFT\JsonRPC\Transport;

class BasicServer implements Transport {

    public function receive(): string {
        return @file_get_contents('php://input');
    }

    public function reply(string $data) {
        echo $data;
    }

}
