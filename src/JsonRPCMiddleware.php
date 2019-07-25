<?php

namespace NGSOFT\JsonRPC;

use NGSOFT\JsonRPC\Transport\PSR7Server;
use Psr\Http\{
    Message\ResponseFactoryInterface, Message\ResponseInterface, Message\ServerRequestInterface, Server\MiddlewareInterface,
    Server\RequestHandlerInterface
};

class JsonRPCMiddleware implements MiddlewareInterface {

    /** @var ResponseFactoryInterface */
    private $responsefactory;

    /** @var object */
    private $handler;

    /** @var string */
    private $path;

    /**
     * Autodetects json requests on an assigned path
     * and respond to it with a declared handler
     *
     * @param string|object $rpchandler
     * @param ResponseFactoryInterface $responsefactory
     * @param string $pathname Respond to that pathname
     */
    public function __construct(
            $rpchandler,
            ResponseFactoryInterface $responsefactory,
            string $pathname = "/rpc.json"
    ) {
        $this->responsefactory = $responsefactory;
        $this->handler = $rpchandler;
        $this->path = $pathname;
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
            $response = $rpc->receive();
            if ($response instanceof ResponseInterface) return $response;
        }

        //not a jsonrpc request or an error occured
        return $handler->handle($request);
    }

}
