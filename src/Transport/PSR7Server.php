<?php

namespace NGSOFT\JsonRPC\Transport;

use NGSOFT\JsonRPC\Interfaces\Transport;
use Psr\Http\Message\{
    ResponseFactoryInterface, ResponseInterface, ServerRequestInterface
};

class PSR7Server implements Transport {

    /** @var ServerRequestInterface */
    protected $request;

    /** @var ResponseFactoryInterface|ResponseInterface */
    protected $responseFactory;

    /**
     * @param ServerRequestInterface $request
     * @param ResponseFactoryInterface|ResponseInterface $response
     */
    public function __construct(
            ServerRequestInterface $request,
            $response
    ) {
        $this->request = $request;
        assert(
                $response instanceof ResponseFactoryInterface
                or $response instanceof ResponseInterface
        );
        $this->responseFactory = $response;
    }

    public function receive(): string {

        if (
                preg_match('/json/', $this->request->getHeaderLine('Content-Type'))
                and in_array($this->request->getMethod(), [
                    'POST', 'PUT', 'DELETE', 'PATCH'
                ])
        ) {
            return (string) $this->request->getBody();
        }
        return "";
    }

    /**
     * @param string $data
     * @return ResponseInterface
     */
    public function reply(string $data) {

        if ($this->responseFactory instanceof ResponseFactoryInterface) {
            $response = $this->responseFactory->createResponse(200);
        } else $response = $this->responseFactory->withStatus(200);

        $response->getBody()->write($data);

        return $response->withHeader('Content-Type', 'application/json')
                        //->withAddedHeader('Access-Control-Allow-Origin', '*')
                        ->withAddedHeader('Cache-Control', 'private, max-age=0, no-cache')
                        ->withAddedHeader('Content-Length', "" . strlen($data));
    }

}
