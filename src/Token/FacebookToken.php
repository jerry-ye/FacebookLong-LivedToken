<?php

namespace IHdigital\Facebook\Token;

class FacebookToken {

    const FB_API_URL = 'https://graph.facebook.com/';

    private $client;
    private $short_lived_access_token;
    private $long_lived_access_token;
    private $app_id;
    private $app_url;
    private $app_secret;
    private $page_access_tokens = array();

    /**
     * init facebook token.
     * @param type $app_id
     * @param type $app_secret
     * @param type $short_lived_access_token
     */
    public function __construct($app_id, $app_secret, $app_url, $short_lived_access_token) {
        $this->client = new \GuzzleHttp\Client();
        $this->app_id = $app_id;
        $this->app_secret = $app_secret;
        $this->app_url = $app_url;
        $this->short_lived_access_token = $short_lived_access_token;
    }

    public function getLongLivedAccessToken() {
        return $this->makeAsPermanentAccessToken($this->getLongAccessToken($this->getCode()));
    }

    public function setAccessToken($short_lived_access_token) {
        $this->short_lived_access_token = $short_lived_access_token;
    }

    public function getAccessToken() {
        return $this->long_lived_access_token;
    }

    public function getPageAccessTokens() {
        return $this->page_access_tokens;
    }

    /**
     * Prepare code for exchange token.
     * @return object|boolean
     */
    function getCode() {
        $res = $this->client->request('GET', self::FB_API_URL . 'oauth/client_code', [
            'query' => [
                'access_token' => $this->short_lived_access_token,
                'client_id' => $this->app_id,
                'client_secret' => $this->app_secret,
                'redirect_uri' => $this->app_url
            ]
        ]);
        return $res->getStatusCode() == 200 ? json_decode($res->getBody()) : FALSE;
    }

    /**
     * exchange short lived code to 60 days long lived access token.
     * @param type $code
     * @return type
     */
    function getLongAccessToken($code) {
        if (!isset($code->code)) {
            return FALSE;
        }
        $exchange_access_token_url = self::FB_API_URL . 'oauth/access_token';
        $res = $this->client->request('GET', $exchange_access_token_url, [
            'query' => [
                'code' => $code->code,
                'grant_type' => 'fb_exchange_token',
                'fb_exchange_token' => $this->short_lived_access_token,
                'client_id' => $this->app_id,
                'client_secret' => $this->app_secret,
                'redirect_uri' => $this->app_url
            ]
        ]);
        return $res->getStatusCode() == 200 ? json_decode($res->getBody()) : FALSE;
    }

    /**
     * make long lived access as never expired.
     * it need page_manage permisson and you have managed at least 1 page.
     * 
     * @param type $long_lived_token
     * @return type
     */
    function makeAsPermanentAccessToken($long_lived_token) {
        if (!isset($long_lived_token->access_token)) {
            return FALSE;
        }
        $this->long_lived_access_token = $long_lived_token->access_token;
        $account_access_token_url = self::FB_API_URL . 'me/accounts';
        $res = $this->client->request('GET', $account_access_token_url, [
            'query' => [
                'access_token' => $this->long_lived_access_token,
                'client_id' => $this->app_id,
                'redirect_uri' => $this->app_url
            ]
        ]);

        if ($res->getStatusCode() == 200) {
            $res = json_decode($res->getBody());
            $this->page_access_tokens = $res->data;
        }
        return $this->long_lived_access_token;
    }

}
