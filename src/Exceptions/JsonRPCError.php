<?php

namespace NGSOFT\JsonRPC\Exceptions;

class JsonRPCError extends \RuntimeException {

    const ERR_PARSE = -32700;
    const ERR_REQUEST = -32600;
    const ERR_METHOD = -32601;
    const ERR_PARAMS = -32602;
    const ERR_INTERNAL = -32603;
    const ERR_SERVER = -32000;

    public function __construct(
            int $statusCode = self::ERR_INTERNAL
    ) {
        parent::__construct("", $statusCode);
    }

}
