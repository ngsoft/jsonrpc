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
            int $statusCode = self::ERR_INTERNAL,
            string $method = null,
            string $param = null
    ) {

        $message = "";
        if ($method !== null) $message .= "An error occured with method $method ";
        if ($param !== null) $message .= " Invalid param $param";

        parent::__construct($message, $statusCode);
    }

}
