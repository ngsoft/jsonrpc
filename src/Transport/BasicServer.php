<?php

namespace NGSOFT\JsonRPC\Transport;

class BasicServer {

    public function receive() {
        return @file_get_contents('php://input');
    }

    public function reply($data) {
        echo $data;
        exit;
    }

}
