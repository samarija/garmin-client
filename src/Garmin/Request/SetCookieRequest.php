<?php

namespace Samarija\Garmin\Request;

use GuzzleHttp\Psr7\Request;
use Samarija\Garmin\Http\GarminConstants;
use Samarija\Garmin\Http\Method;
use Samarija\Garmin\Http\Uri;

class SetCookieRequest extends Request
{
    public function __construct()
    {
        parent::__construct(
            Method::GET->value,
            new Uri(
                GarminConstants::SSO_BASE_URL.'/embed',
                GarminConstants::GET_COOKIE_PARAMS
            )
        );
    }
}
