<?php

declare(strict_types=1);

namespace NGSOFT\JsonRPC;

use Exception;
use NGSOFT\JsonRPC\{
    Base\Request, Base\Response, Base\Rpc, Exceptions\JsonRPCError, Interfaces\Transport, Transport\BasicServer
};
use Psr\Log\LoggerInterface,
    ReflectionClass,
    ReflectionProperty,
    Throwable;

class Server {

    /** @var array|null|int */
    protected $error = null;

    /** @var ReflectionProperty|null */
    protected $handlerError = null;

    /** @var object */
    protected $handler;

    /** @var Transport */
    protected $transport;

    /** @var LoggerInterface|null */
    protected $logger;

    /** @var bool */
    protected $assoc = false;

    /** @var array */
    protected $requests = array();

    /** @var array */
    protected $responses = array();

    /** @var ReflectionClass|null */
    protected $refClass;

    /**
     * @param string|object $methodHandler class name or instance
     * @param Transport|null $transport
     */
    public function __construct($methodHandler, Transport $transport = null) {

        assert(
                is_object($methodHandler)
                or ( is_string($methodHandler) and class_exists($methodHandler))
        );

        $this->handler = is_string($methodHandler) ? new $methodHandler() : $methodHandler;
        $this->transport = $transport ?: new BasicServer();
    }

    /**
     * Handles the request
     * @param string|null $input
     * @return mixed
     */
    public function receive(string $input = null) {
        $this->init();
        try {
            $input = $input ?: $this->transport->receive();
            $json = $this->process($input);
            return $this->transport->reply($json);
        } catch (Throwable $e) {
            $this->logException($e);
            //output a basic error response?
        }
        return;
    }

    /**
     * Set the Transport Class
     * @param Transport $transport
     */
    public function setTransport(Transport $transport) {
        $this->transport = $transport;
    }

    /**
     * Set the Logger
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    /**
     * Use Assoc
     */
    public function setObjectsAsArrays() {
        $this->assoc = true;
    }

    protected function process(string $json): string {

        if (!$struct = Rpc::decode($json, $batch)) {
            $code = is_null($struct) ? Rpc::ERR_PARSE : Rpc::ERR_REQUEST;
            $data = new Response();
            $data->createStdError($code);
            return $data->toJson();
        }

        $this->getRequests($struct);
        $this->processRequests();

        $data = implode(',', $this->responses);

        return $batch && $data ? '[' . $data . ']' : $data;
    }

    protected function getRequests($struct) {

        if (is_array($struct)) {

            foreach ($struct as $item) {
                $this->requests[] = new Request($item);
            }
        } else {
            $this->requests[] = new Request($struct);
        }
    }

    protected function processRequests() {

        foreach ($this->requests as $request) {

            # check if we got an error parsing the request, otherwise process it
            if ($request->fault) {

                $this->error = array(
                    'code' => Rpc::ERR_REQUEST,
                    'data' => $request->fault
                );

                # we always response to request errors
                $this->addResponse($request, null);

                continue;
            }
            $method = preg_replace('/[\-\.]/', '_', $request->method);

            $result = $this->processRequest($method, $request->params);

            if (!$request->notification) {
                $this->addResponse($request, $result);
            }
        }
    }

    protected function processRequest($method, $params) {


        $this->error = null;

        if (!$callback = $this->getCallback($method)) {
            $this->error = Rpc::ERR_METHOD;

            return;
        }

        if (!$this->checkMethod($method, $params)) {
            $this->error = Rpc::ERR_PARAMS;

            return;
        }

        if ($this->assoc) {
            $this->castObjectsToArrays($params);
        }

        try {
            $result = call_user_func_array($callback, $params);
        } catch (Throwable $e) {
            $this->logException($e);
            if ($e instanceof JsonRPCError) {
                $code = $e->getCode();
                if ($code !== 0) {
                    $this->error = [
                        "code" => $code,
                        "message" => $e->getMessage()
                    ];
                    return;
                }
            }
            $this->error = Rpc::ERR_INTERNAL;
            return;
        }

        if ($this->error = $this->getHandlerError()) {
            $this->clearHandlerError();
        }

        return $result;
    }

    protected function addResponse($request, $result) {

        $ar = array(
            'id' => $request->id
        );

        if ($this->error) {
            $ar['error'] = $this->error;
        } else {
            $ar['result'] = $result;
        }

        $response = new Response();

        if (!$response->create($ar)) {
            $this->logError($response->fault);
            $response->createStdError(Rpc::ERR_INTERNAL, $request->id);
        }

        $this->responses[] = $response->toJson();
    }

    protected function getCallback($method) {

        $callback = array($this->handler, $method);

        if (is_callable($callback)) {
            return $callback;
        }
    }

    protected function checkMethod($method, &$params) {

        try {

            if (!$this->refClass) {
                # we have already checked that handler is callable
                $this->refClass = new ReflectionClass($this->handler);

                try {

                    $prop = $this->refClass->getProperty('error');

                    if ($prop->isPublic()) {
                        $this->handlerError = $prop;
                    }
                } catch (Exception $e) {
                    $e->getCode();
                }
            }


            try {
                $refMethod = $this->refClass->getMethod($method);
            } catch (Exception $e) {
                $e->getCode();
                # we know we are callable, so the class must be implementing __call or __callStatic
                $params = $this->getParams($params);
                return true;
            }

            $res = true;

            if (is_object($params)) {

                $named = (array) $params;
                $params = array();
                $refParams = $refMethod->getParameters();

                foreach ($refParams as $arg) {

                    $argName = $arg->getName();

                    if (array_key_exists($argName, $named)) {
                        $params[] = $named[$argName];
                        unset($named[$argName]);
                    } elseif (!$arg->isOptional()) {
                        $res = false;
                        break;
                    }
                }

                if ($extra = array_values($named)) {
                    $params = array_merge($params, $extra);
                }
            } else {
                $params = $this->getParams($params);
                $reqArgs = $refMethod->getNumberOfRequiredParameters();
                $res = count($params) >= $reqArgs;
            }

            return $res;
        } catch (Exception $e) {
            $e->getCode();
        }
    }

    protected function getParams($params) {

        if (is_object($params)) {
            $params = array_values((array) $params);
        } elseif (is_null($params)) {
            $params = array();
        }

        return $params;
    }

    protected function castObjectsToArrays(&$params) {

        foreach ($params as &$param) {

            if (is_object($param)) {
                $param = (array) $param;
            }
        }
    }

    protected function getHandlerError() {

        if ($this->handlerError) {

            if ($this->handlerError->isStatic()) {
                return $this->handlerError->getValue();
            } else {
                return $this->handlerError->getValue($this->handler);
            }
        }
    }

    protected function clearHandlerError() {

        if ($this->handlerError) {

            if ($this->handlerError->isStatic()) {
                return $this->handlerError->setValue(null);
            } else {
                return $this->handlerError->setValue($this->handler, null);
            }
        }
    }

    protected function logException(Throwable $e) {
        $message = 'Exception: ' . $e->getMessage();
        $message .= ' in ' . $e->getFile() . ' on line ' . $e->getLine();
        $this->logError($message);
    }

    protected function logError(string $message) {
        if ($this->logger) $this->logger->error($message);
        else error_log($message);
    }

    protected function init() {
        $this->requests = array();
        $this->responses = array();
        $this->error = null;
        $this->handlerError = null;
        $this->refClass = null;
    }

}
