<?php

namespace NGSOFT\JsonRPC\Exceptions;

class JsonRPCError extends \RuntimeException {

    const ERR_PARSE = -32700;
    const ERR_REQUEST = -32600;
    const ERR_METHOD = -32601;
    const ERR_PARAMS = -32602;
    const ERR_INTERNAL = -32603;
    const ERR_SERVER = -32000;
    const ERR_APPLICATION = -32500;
    const ERR_TRANSPORT = -32300;

    private static $messages = [
        self::ERR_PARSE => "Parse error",
        self::ERR_REQUEST => "Invalid Request",
        self::ERR_METHOD => "Method not found",
        self::ERR_PARAMS => "Invalid params",
        self::ERR_INTERNAL => "Internal error",
        self::ERR_SERVER => "Server error",
        self::ERR_APPLICATION => "Application error",
        self::ERR_TRANSPORT => "Transport error"
    ];

    /**
     *
     * @param int $statusCode JsonRPC Status code
     * @param string $message
     * @param type $data
     */
    public function __construct(
            int $statusCode = self::ERR_INTERNAL,
            string $message = ""
    ) {
        if (empty($message) and isset(self::$messages[$statusCode])) $message = self::$messages[$statusCode];
        parent::__construct($message, $statusCode);
    }

}
