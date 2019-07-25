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

    /** @var bool */
    protected $cors;

    /**
     * @param ServerRequestInterface $request
     * @param ResponseFactoryInterface|ResponseInterface $response
     * @param bool $cors Enable all origins (CORS)
     */
    public function __construct(
            ServerRequestInterface $request,
            $response,
            bool $cors = false
    ) {
        $this->request = $request;
        assert(
                $response instanceof ResponseFactoryInterface
                or $response instanceof ResponseInterface
        );
        $this->responseFactory = $response;
        $this->cors = $cors;
    }

    /** {@inheritdoc} */
    public function receive(): string {

        if (
                preg_match('/json/', $this->request->getHeaderLine('Content-Type'))
                and preg_match('/json/', $this->request->getHeaderLine('Accept'))
                and in_array($this->request->getMethod(), [
                    'POST',
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
        return $this->respond($response, $data);
    }

    /**
     * Adds Headers + json to response
     * @param ResponseInterface $response
     * @param string $json
     * @return ResponseInterface
     */
    public function respond(ResponseInterface $response, string $json): ResponseInterface {
        //notification
        if (empty($json)) $response = $response->withStatus(204);
        $response->getBody()->write($json);

        if ($this->cors) $response = $response->withHeader('Access-Control-Allow-Origin', '*');
        return $response->withHeader('Content-Type', 'application/rpc+json')
                        ->withHeader('Cache-Control', 'private, max-age=0, no-cache')
                        ->withHeader('Content-Length', "" . strlen($json));
    }

}
