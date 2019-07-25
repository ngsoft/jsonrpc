<?php

namespace NGSOFT\JsonRPC\Transport;

use NGSOFT\JsonRPC\Interfaces\Transport;

class BasicServer implements Transport {

    /** {@inheritdoc} */
    public function receive(): string {
        $stream = fopen("php://input", "r");
        fseek($stream, 0);
        $json = stream_get_contents($stream);
        fclose($stream);
        return is_string($json) ? $json : "";
    }

    /** {@inheritdoc} */
    public function reply(string $data) {
        echo $data;
    }

}
