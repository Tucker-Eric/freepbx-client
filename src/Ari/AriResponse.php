<?php

namespace FreePbx\Ari;

use Psr\Http\Message\ResponseInterface;

class AriResponse
{
    /**
     * @var ResponseInterface
     */
    private $_response;

    /**
     * @var mixed
     */
    protected $body;

    public function __construct(ResponseInterface $response)
    {
        $this->_response = $response;
        $this->body = json_decode((string)$response->getBody());
    }

    public function __toString()
    {
        return (string)$this->_response->getBody();
    }

    /**
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Pass all calls to the response
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return $this->_response->$name(...$arguments);
    }
}
