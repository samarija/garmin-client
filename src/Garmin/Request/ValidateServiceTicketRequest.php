<?php

namespace Samarija\Garmin\Request;

use GuzzleHttp\Psr7\Request;
use Samarija\Garmin\Http\GarminConstants;
use Samarija\Garmin\Http\Method;
use Samarija\Garmin\Http\Uri;

class ValidateServiceTicketRequest extends Request
{
    public function __construct(string $serviceTicket)
    {
        parent::__construct(
            Method::GET->value,
            new Uri(
                GarminConstants::CONNECT_MODERN_URL,
                [
                    'ticket' => $serviceTicket,
                ],
            ),
            [
                'DNT' => 1,
                'Referer' => GarminConstants::SSO_EMBED_URL,
                'TE' => 'Trailers',
            ]
        );
    }
}
