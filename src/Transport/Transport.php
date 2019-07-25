<?php

declare(strict_types=1);

namespace NGSOFT\JsonRPC\Transport;

interface Transport {

    /**
     * Get the request
     * @return string
     */
    public function receive(): string;

    /**
     * Send the response
     * @param string $data
     */
    public function reply(string $data);
}
