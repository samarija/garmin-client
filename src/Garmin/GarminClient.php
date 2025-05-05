<?php

namespace Samarija\Garmin;

use stdClass;
use Exception;
use Samarija\Garmin\Http;
use GuzzleHttp\Client;
use Illuminate\Support\Facades;
use Samarija\Garmin\Response\CsrfToken;
use Samarija\Garmin\Http\GarminConstants;
use Samarija\Garmin\Request\LoginRequest;
use GuzzleHttp\Cookie\FileCookieJar;
use Samarija\Garmin\Response\LoginResponse;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Samarija\Garmin\Request\CSRFTokenRequest;
use Samarija\Garmin\Request\SetCookieRequest;
use Samarija\Garmin\support\ActivityDownload;
use GuzzleHttp\Exception\GuzzleException;
use Samarija\Garmin\Request\AccessTokenRequest;
use Samarija\Garmin\Request\RetrieveGpxRequest;
use Samarija\Garmin\Request\ServiceTicketRequest;
use Samarija\Garmin\Response\AccessTokenResponse;
use Samarija\Garmin\Request\RetrieveCourseRequest;
use Psr\Http\Client\ClientExceptionInterface;
use Samarija\Garmin\Request\RetrieveCoursesRequest;
use Samarija\Garmin\Response\ServiceTicketResponse;
use Samarija\Garmin\Request\DownloadActivityRequest;
use Samarija\Garmin\Request\RetrieveActivityRequest;
use Samarija\Garmin\Request\RetrieveActivitiesRequest;
use Samarija\Garmin\Response\DownloadActivityResponse;
use Samarija\Garmin\Request\DownloadActivityTcxRequest;
use Samarija\Garmin\Request\RetrieveActivityTypesRequest;
use Samarija\Garmin\Request\RetrieveActivityExerciseSetsRequest;


class GarminClient
{
    private Client $client;

    private $l_client;

    private string $username;

    private string $password;

    private string $cookieDir = '';

    private string $cookieFile = '';

    private Http\AccessToken $accessToken;

    public function __construct(string $cookiePath = 'cookie_jar.txt')
    {
        $cookieJar = new FileCookieJar($cookiePath, true);
        $this->client = new Client(['cookies' => $cookieJar,  'verify' => false]);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws GuzzleException
     * @throws Exception
     */
    public function login(string $username, string $password): self
    {
        $response = $this->client->send(new SetCookieRequest);

        if ($response->getStatusCode() !== 200) {
            throw new Exception($response->getReasonPhrase());
        }

        $response = $this->client->send(new CSRFTokenRequest);
        $csrfToken = CsrfToken::fromResponse($response);

        $response = new LoginResponse(
            $this->client
                ->sendRequest(
                    new LoginRequest(
                        $username,
                        $password,
                        $csrfToken
                    )
                )
        );

        $serviceTicket = $response->getServiceTicket();

        $response = new ServiceTicketResponse(
            $this->send(
                new ServiceTicketRequest($serviceTicket)
            )
        );

        $response->validate();

        $response = new AccessTokenResponse(
            $this->client->send(new AccessTokenRequest)
        );

        $this->accessToken = $response->token();

        return $this;
    }
    
    private function initClient(): void
    {
        $cookiePath = $this->cookieFile;

        if ($this->cookieDir !== '') {
            $cookiePath = $this->cookieDir.'/'.$this->cookieFile;
        }

        $cookieJar = new FileCookieJar($cookiePath, true);

        if (! isset($this->client)) {
            // $this->client = Facades\Http::withOptions(['cookies' => $cookieJar, 'verify' => false]);
            $this->client = new Client(['cookies' => $cookieJar,  'verify' => false]);
        }
    }

    /**
     * @throws GuzzleException
     * @throws ClientExceptionInterface
     */
    public function getCourses(): ?array
    {
        $request = new RetrieveCoursesRequest($this->accessToken->accessToken);
        $response = $this->send($request);
        $data = json_decode($response->getBody()->getContents(), false);

        return $data->coursesForUser ?? null;
    }

    public function getCourse(string $courseId): stdClass
    {
        $request = new RetrieveCourseRequest($this->accessToken->accessToken, $courseId);
        $response = $this->send($request);

        return json_decode($response->getBody()->getContents(), false);
    }

    public function getCourseGpx(string $courseId): string
    {
        $request = new RetrieveGpxRequest($this->accessToken->accessToken, $courseId);
        $response = $this->send($request);

        return $response->getBody()->getContents();
    }

    /**
     * @return stdClass[]
     *
     * @throws ClientExceptionInterface
     */
    public function getActivities(int $start = 20, int $limit = 20, string $sortBy = 'startLocal', string $sortOrder = 'asc'): array
    {
        $request = new RetrieveActivitiesRequest(token: $this->accessToken->accessToken, start: $start, limit: $limit, sortby: $sortBy, sortOrder: $sortOrder);
        $response = $this->send($request);

        return json_decode($response->getBody()->getContents(), false);
    }

    /**
     * @throws GuzzleException
     * @throws ClientExceptionInterface
     */
    public function getActivitiesBetweenDates(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate, int $start = 0, int $limit = 20, string $sortBy = 'startLocal', string $sortOrder = 'asc'): array
    {
        $request = new RetrieveActivitiesRequest(token: $this->accessToken->accessToken, start: $start, limit: $limit, startDate: $startDate, endDate: $endDate, sortby: $sortBy, sortOrder: $sortOrder);
        $response = $this->send($request);

        return json_decode($response->getBody()->getContents(), false);
    }

    public function getActivityTypes(): array
    {
        $request = new RetrieveActivityTypesRequest(token: $this->accessToken->accessToken);
        $response = $this->send($request);

        return json_decode($response->getBody()->getContents(), false);
    }

    /**
     * @throws GuzzleException
     * @throws ClientExceptionInterface
     *
     * @see Client::send()
     */
    private function send(RequestInterface $request, array $options = [], bool $retryOnFail = true): ResponseInterface
    {
        try {
            return $this->client->send($request, $options);
        } catch (GuzzleException $exception) {
            if ($retryOnFail) {
                if ($exception->getCode() === 401) {
                    $this->login();
                }

                return $this->send($request, $options, false);
            }
            throw $exception;
        }
    }

    public function getActivity(int $activityId)
    {
        $request = new RetrieveActivityRequest($this->accessToken->accessToken, $activityId);
        $response = $this->send($request);

        return json_decode($response->getBody()->getContents(), false);
    }

    public function getActivitySets(int $activityId)
    {
        $request = new RetrieveActivityExerciseSetsRequest($this->accessToken->accessToken, $activityId);
        $response = $this->send($request);

        return json_decode($response->getBody()->getContents(), false);
    }
    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function downloadActivity(string $activityId, string $path): bool
    {
        $response = $this->client->send(new DownloadActivityRequest($this->accessToken->accessToken, $activityId));
        $downloadResponse = new DownloadActivityResponse($response);
        $downloadResponse->download($path);

        return true;
    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function downloadActivityTcx(string $activityId, string $path): bool
    {
        $response = $this->client->send(new DownloadActivityTcxRequest($this->accessToken->accessToken, $activityId));
        $downloadResponse = new DownloadActivityResponse($response);
        $downloadResponse->download($path);

        return true;
    }

    /**
     * @throws GuzzleException
     */
    public function downloadActivityAsStreamObject(string $activityId): ActivityDownload
    {
        $response = $this->client->send(new DownloadActivityRequest($this->accessToken->accessToken, $activityId));
        $downloadResponse = new DownloadActivityResponse($response);

        return new ActivityDownload($downloadResponse->getFileName(), $downloadResponse->getBody());
    }
}
