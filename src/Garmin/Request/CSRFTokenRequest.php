<?php

namespace Samarija\Garmin\Request;

use GuzzleHttp\Psr7\Request;
use Samarija\Garmin\Http\GarminConstants;
use Samarija\Garmin\Http\Method;
use Samarija\Garmin\Http\Uri;
use Psr\Http\Message\UriInterface;

class CSRFTokenRequest extends Request
{
    public function __construct()
    {
        parent::__construct(
            Method::GET->value,
            new Uri(
                GarminConstants::SSO_BASE_URL.'/signin',
                GarminConstants::CSRF_TOKEN_PARAMS,
            ),
            [
                'Referer' => (new SetCookieRequest)->getUri()->__toString(),
            ]
        );
    }

    public static function url(): UriInterface
    {
        return (new self)->getUri();
    }
}
