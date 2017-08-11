<?php

namespace FreePbx\RestApi;

use Psr\Http\Message\ResponseInterface;

class RestApiResponse
{
    /**
     * @var ResponseInterface
     */
    protected $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * Pass all calls to the response object
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return $this->response->$name(...$arguments);
    }

    /**
     * @return mixed
     */
    public function getBody($asArray = false)
    {
        return json_decode((string)$this->response->getBody(), $asArray);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->getBody(true);
    }
}
