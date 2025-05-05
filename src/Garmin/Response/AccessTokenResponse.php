<?php

namespace Samarija\Garmin\Response;

use GuzzleHttp\Psr7\Response;
use Samarija\Garmin\Http\AccessToken;
use Psr\Http\Message\ResponseInterface;

class AccessTokenResponse extends Response
{
    public function __construct(ResponseInterface $response)
    {
        parent::__construct(
            $response->getStatusCode(),
            $response->getHeaders(),
            $response->getBody(),
            $response->getProtocolVersion(),
            $response->getReasonPhrase()
        );
    }

    public function token(): AccessToken
    {
        return AccessToken::fromJson(json_decode($this->getBody(), true));
    }
}
