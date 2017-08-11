<?php

namespace FreePbx\RestApi;

class AuthClient
{
    private $_token;

    private $_key;

    private $_nonce;

    public function __construct(array $credentials = [])
    {
        $this->_token = $credentials['token'];
        $this->_key = $credentials['key'];
    }

    /**
     * @param $verb
     * @param $url
     * @param string $body
     * @return string
     */
    public function createSignature($verb, $url, $body = '')
    {
        $parts = [
            hash('sha256', preg_replace('/https?:\/\//', '', $url).':'.strtolower($verb)),
            hash('sha256', $this->_token.':'.$this->nonce()),
            hash('sha256', base64_encode($body))
        ];

        $data = hash('sha256', implode(':', $parts));

        return hash_hmac('sha256', $data, $this->_key);
    }

    /**
     * @param $verb
     * @param $endpoint
     * @param string $body
     * @return array
     */
    public function getHeaders($verb, $endpoint, $body = '')
    {
        $signature = $this->createSignature($verb, $endpoint, $body);

        return [
            'SIGNATURE' => $signature,
            'TOKEN'     => $this->getToken(),
            'NONCE'     => $this->nonce()
        ];
    }

    /**
     * @return string
     */
    public function nonce()
    {
        if($this->_nonce) {
            return $this->_nonce;
        }

        return $this->_nonce = md5(microtime());
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->_token;
    }
}
