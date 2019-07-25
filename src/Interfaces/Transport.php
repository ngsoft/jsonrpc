<?php

declare(strict_types=1);

namespace NGSOFT\JsonRPC\Interfaces;

interface Transport {

    /**
     * Get the request
     * @return string
     */
    public function receive(): string;

    /**
     * Send the response
     * Can return a value
     * @param string $data
     */
    public function reply(string $data);
}
