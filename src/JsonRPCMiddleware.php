<?php

namespace NGSOFT\JsonRPC;

use NGSOFT\JsonRPC\Transport\PSR7Server;
use Psr\{
    Http\Message\ResponseFactoryInterface, Http\Message\ResponseInterface, Http\Message\ServerRequestInterface,
    Http\Server\MiddlewareInterface, Http\Server\RequestHandlerInterface, Log\LoggerInterface
};

class JsonRPCMiddleware implements MiddlewareInterface {

    /** @var ResponseFactoryInterface */
    private $responsefactory;

    /** @var object */
    private $handler;

    /** @var string */
    private $path;

    /** @var LoggerInterface */
    private $logger;

    /**
     * Autodetects json requests on an assigned path
     * and respond to it with a declared handler
     *
     * @param string|object $rpchandler
     * @param ResponseFactoryInterface $responsefactory
     * @param string $pathname Respond to that pathname
     * @param LoggerInterface|null $logger If you have a logger
     */
    public function __construct(
            $rpchandler,
            ResponseFactoryInterface $responsefactory,
            string $pathname = "/rpc.json",
            LoggerInterface $logger = null
    ) {
        $this->responsefactory = $responsefactory;
        $this->handler = $rpchandler;
        $this->path = $pathname;
        $this->logger = $logger;
    }

    /** {@inheritdoc} */
    public function process(
            ServerRequestInterface $request,
            RequestHandlerInterface $handler
    ): ResponseInterface {

        if (
                $request->getUri()->getPath() === $this->path
                and preg_match('/json/', $request->getHeaderLine('Content-Type'))
                and in_array($request->getMethod(), [
                    'POST', 'PUT', 'DELETE', 'PATCH'
                ])
        ) {
            $transport = new PSR7Server($request, $this->responsefactory);
            $rpc = new Server($this->handler, $transport);
            if ($this->logger instanceof LoggerInterface) $rpc->setLogger($logger);
            $response = $rpc->receive();
            if ($response instanceof ResponseInterface) return $response;
        }

        //not a jsonrpc request or an error occured
        return $handler->handle($request);
    }

}
