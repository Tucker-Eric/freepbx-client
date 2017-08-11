<?php

namespace FreePbx\RestApi;

use GuzzleHttp\Client;

class RestApiClient
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var AuthClient
     */
    protected $auth;

    public function __construct(array $credentials = [])
    {
        $this->url = $credentials['host'].':'.$credentials['port'];
        $this->client = new Client(['base_uri' => $this->url]);
        $this->auth = new AuthClient($credentials);
    }

    /**
     * @return Client
     */
    protected function getClient()
    {
        return $this->client;
    }

    /**
     * @param string $endpoint
     * @return string
     */
    public function getEndpoint($endpoint = '')
    {
        return $this->url.'/rest.php/rest'.($endpoint[0] !== '/' ? '/' : '').$endpoint;
    }

    /**
     * @param $uri
     * @return RestApiResponse
     */
    public function get($uri)
    {
        $url = $this->getEndpoint($uri);
        $resp = $this->client->get($url, ['headers' => $this->auth->getHeaders('get', $url)]);

        return new RestApiResponse($resp);
    }
}
