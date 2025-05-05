<?php

namespace Samarija\Garmin\Request;

use GuzzleHttp\Psr7\Request;
use Samarija\Garmin\Http\GarminConstants;
use Samarija\Garmin\Http\Method;
use Samarija\Garmin\Http\Uri;

class RetrieveCourseRequest extends Request
{
    public function __construct(string $token, string $courseId)
    {
        parent::__construct(
            Method::GET->value,
            new Uri(
                GarminConstants::CONNECT_BASE_URL.'/course-service/course/'.$courseId,
            ),
            [
                'NK' => 'NT',
                'X-app-ver' => GarminConstants::APP_VERSION,
                'X-lang' => 'nl-NL',
                'DI-Backend' => 'connectapi.garmin.com',
                'DNT' => '1',
                'Sec-GPC' => '1',
                'Connection' => 'keep-alive',
                'Referer' => GarminConstants::CONNECT_BASE_URL.'/course-service/course/'.$courseId,
                'Pragma' => 'no-cache',
                'Cache-Control' => 'no-cache',
                'TE' => 'trailers',
                'Authorization' => sprintf('Bearer %s', $token),
                'Accept' => 'application/json',
            ]
        );
    }
}
