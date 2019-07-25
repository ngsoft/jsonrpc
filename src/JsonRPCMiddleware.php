<?php

declare(strict_types=1);

namespace NGSOFT\JsonRPC;

use NGSOFT\JsonRPC\Transport\PSR7Server;
use Psr\{
    Http\Message\ResponseFactoryInterface, Http\Message\ResponseInterface, Http\Message\ServerRequestInterface,
    Http\Server\MiddlewareInterface, Http\Server\RequestHandlerInterface, Log\LoggerInterface
};

class JsonRPCMiddleware implements MiddlewareInterface {

    /** @var ResponseFactoryInterface */
    private $responsefactory;

    /** @var string|object */
    private $handler;

    /** @var string */
    private $path;

    /** @var LoggerInterface */
    private $logger;

    /** @var bool */
    private $cors;

    /**
     * Autodetects json requests on an assigned path
     * and respond to it with a declared handler
     *
     * @param string|object $rpchandler Class name or instance
     * @param ResponseFactoryInterface $responsefactory
     * @param string $pathname Respond to that pathname
     * @param bool $cors Description
     * @param LoggerInterface|null $logger If you have a logger
     */
    public function __construct(
            $rpchandler,
            ResponseFactoryInterface $responsefactory,
            string $pathname = "/rpc.json",
            bool $cors = false,
            LoggerInterface $logger = null
    ) {
        $this->responsefactory = $responsefactory;
        $this->handler = $rpchandler;
        $this->path = $pathname;
        $this->logger = $logger;
        $this->cors = $cors;
    }

    /** {@inheritdoc} */
    public function process(
            ServerRequestInterface $request,
            RequestHandlerInterface $handler
    ): ResponseInterface {

        if (
                $request->getUri()->getPath() === $this->path
                and preg_match('/json/', $request->getHeaderLine('Content-Type'))
                and preg_match('/json/', $request->getHeaderLine('Accept'))
                and in_array($request->getMethod(), [
                    'POST',
                ])
        ) {
            $transport = new PSR7Server($request, $this->responsefactory, $this->cors);
            $rpc = new Server($this->handler, $transport);
            if ($this->logger instanceof LoggerInterface) $rpc->setLogger($this->logger);
            $response = $rpc->receive();
            if ($response instanceof ResponseInterface) return $response;
            else {
                //an exception has been thrown and logged so invalid request
                $json = json_encode([
                    "jsonrpc" => "2.0",
                    "id" => null,
                    "error" => [
                        "code" => -32600,
                        "message" => "Invalid Request"
                    ]
                ]);
                return $transport->respond($this->responsefactory->createResponse(400), $json);
            }
        }

        //not a jsonrpc request
        return $handler->handle($request);
    }

}
