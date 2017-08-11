<?php

namespace FreePbx\Ari;

use GuzzleHttp\Client as HttpClient;


class AriClient
{
    /**
     * @var HttpClient
     */
    private $_client;

    /**
     * @var string
     */
    private $_apiKey;

    public function __construct(array $config = [])
    {
        $this->_client = new HttpClient(['base_uri' => 'http://'.$config['host'].':8088']);
        $this->_apiKey = $config['username'].':'.$config['password'];
    }

    /**
     * Post wrapper for requests
     * @param $uri
     * @param array $args
     * @param array $body
     * @return AriResponse
     */
    public function post($uri, array $args = [], array $body = [])
    {
        if (! empty($body)) {
            $body = ['json' => $body];
        }

        return new AriResponse($this->_client->post($this->makeUrl($uri, $args), $body));
    }

    /**
     * Get wrapper for reqyests
     * @param $uri
     * @param array $args
     * @return AriResponse
     */
    public function get($uri, array $args = [])
    {
        return new AriResponse($this->_client->get($this->makeUrl($uri, $args)));
    }

    /**
     * Originate a call from extension to a destination
     * @param $from
     * @param $to
     * @param string $context
     * @return AriResponse
     */
    public function originate($from, $to, $context = 'from-internal')
    {
        $params = ['endpoint' => "PJSIP/$from", 'extension' => $to, 'context' => $context, 'timeout' => 30];
        // Passing the callerid to a feature code fucks it up
        if (strpos($to, '*') === false) {
            $params['callerId'] = $to;
        }

        return $this->post('channels', $params);
    }

    /**
     * @param $ext
     * @return mixed
     */
    public function extensionStatus($ext)
    {
        return $this->get("endpoints/PJSIP/$ext")->getBody()->state;
    }

    /**
     * Login/Logout of ALL queues agent is a dynamic member of
     * @param $ext
     * @return AriResponse
     */
    public function toggleLogin($ext)
    {
        return $this->post('channels', [
            'endpoint'  => 'Local/s@app-all-queue-toggle',
            'extension' => (string)$ext,
            'callerId'  => 'Log In/Out'
        ]);
    }

    protected function makeUrl($uri, array $params = [])
    {
        $params['api_key'] = $this->_apiKey;

        return "/ari/$uri?".http_build_query($params);
    }
}
