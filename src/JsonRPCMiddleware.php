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
    private $handler;

    /**
     * @param object $rpchandler
     * @param ResponseFactoryInterface $responsefactory
     */
    public function __construct(
            object $rpchandler,
            ResponseFactoryInterface $responsefactory
    ) {
        $this->responsefactory = $responsefactory;
        $this->handler = $rpchandler;
    }

    /** {@inheritdoc} */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {

        if (
                preg_match('/json/', $this->request->getHeaderLine('Content-Type'))
                and in_array($this->request->getMethod(), [
                    'POST', 'PUT', 'DELETE', 'PATCH'
                ])
        ) {
            $transport = new PSR7Server($request, $this->responsefactory);
            $rpc = new Server($this->handler, $transport);
            $response = $rpc->receive();
            if ($response instanceof ResponseInterface) return $response;
        }

        //not a jsonrpc request
        return $handler->handle($request);
    }

}
