<?php
declare(strict_types=1);

namespace Bitbar\Repository;

use Bitbar\PullRequest;
use Bitbar\ReviewStatus;
use Bitbar\User;
use GuzzleHttp\Client;

final class BitbucketRepository extends AbstractRepository
{
    /**
     * The URL to the API endpoint for fetching REVIEWING PRs
     */
    private const REVIEWING_DATA_URL = 'https://bitbucket.org/api/2.0/repositories/{PROJECT_REPO}/pullrequests?q=state+%3D+%22OPEN%22+AND+reviewers.username+%3D+%22{USERNAME}%22';

    /**
     * The URL to the API endpoint for fetching ISSUED PRs
     */
    private const ISSUED_DATA_URL = 'https://bitbucket.org/api/2.0/pullrequests/{USERNAME}';

    private const PR_DETAILS_URL = 'https://bitbucket.org/api/2.0/repositories/{PROJECT_REPO}/pullrequests/{ID}';

    /**
     * @var string
     */
    private $username;

    /**
     * @var string[] Authentication data used for every request.
     */
    private $authData;

    /**
     * The HTTP Client for connecting to the Bitbucket API.
     *
     * @var Client
     */
    private $client;

    /**
     * The PR cache singleton for this request.
     *
     * @var PullRequest[]
     */
    private $prCache = [];


    /**
     * BitbucketRepository constructor.
     *
     * Sets the config array and initiates the HTTP client.
     *
     * @param string[] $config Config array.
     */
    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->client = new Client();
    }


    /**
     * Logs in to the repository.
     *
     * @param string $username The username used for logging in.
     * @param string $password The password used for logging in.
     * @return void
     */
    public function login(string $username, string $password): void
    {
        $this->username = $username;
        $this->authData = [
            $username,
            $password,
        ];
    }

    /**
     * Returns a list of pull requests for a specific ISSUER.
     *
     * This method returns a list of standardized PullRequest objects.
     *
     * @return PullRequest[]|null
     * @throws \Exception API Exception.
     */
    public function getIssuerPullRequestList(): ?array
    {
        $issuerPrList = $this->getIssuerPRListFromApi();
        $return = [];

        foreach ($issuerPrList as $issuerPr) {
            $return[] = $this->getPullRequestDetails($issuerPr['repository'], (int) $issuerPr['id']);
        }

        return $return;
    }

    /**
     * Returns a list of pull requests for a specific REVIEWER.
     *
     * This method returns a list of standardized PullRequest objects.
     *
     * @param string[] $repositoryList The list of repositories to search in.
     * @return PullRequest[]|null
     * @throws \Exception API Exception.
     */
    public function getReviewerPullRequestList(array $repositoryList): ?array
    {
        $reviewerPrList = $this->getReviewerPRListFromApi($repositoryList);
        $return = [];

        foreach ($reviewerPrList as $reviewerPr) {
            $return[] = $this->getPullRequestDetails($reviewerPr['repository'], (int) $reviewerPr['id']);
        }

        return $return;
    }

    /**
     * Returns the details of a single pull request.
     *
     * @param string $repository The repository where the pull requests is located.
     * @param int $id The unique identifier of the pull request.
     * @return PullRequest
     * @throws \Exception API Exception.
     */
    public function getPullRequestDetails(string $repository, int $id): PullRequest
    {
        // Hash for in-request caching. This prevents unneccessary API calls.
        $prHash = md5($repository . $id);

        // Houston, we have a cache miss!
        if (!isset($this->prCache[$prHash])) {
            try {
                $prData = $this->getPrDetailsFromApi($repository, $id);
            } catch (\Exception $e) {
                throw $e;
            }

            // Get the issuer from the data fetched from the API.
            $issuer = self::getUserFromData($prData['author']);

            // Now build the pull request.
            $pullRequest = new PullRequest();

            // Get the status and add the participants.
            foreach ($prData['participants'] as $participant) {
                if ($participant['role'] === 'REVIEWER') {
                    $user = self::getUserFromData($participant['user']);
                    $pullRequest->reviewers[] = $user;
                    $status = new ReviewStatus();
                    $status->user = $user;
                    $status->approved = (bool) $participant['approved'];
                    if ($participant['approved'] === true) {
                        $pullRequest->numberOfApproves++;
                    }
                    $pullRequest->numberOfReviewers++;
                    $status->lastChanged = self::getDateFromBitbucketString($participant['participated_on']);
                    $pullRequest->reviewStatus[] = $status;
                }
            }

            // And add the rest of the fields to the PR :-).
            $pullRequest->id = $prData['id'];
            $pullRequest->title = $prData['title'];
            $pullRequest->description = $prData['description'];
            $pullRequest->url = $prData['links']['html']['href'];
            $pullRequest->repository = $prData['source']['repository']['full_name'];
            $pullRequest->issuer = $issuer;
            $pullRequest->numberOfComments = (int) $prData['comment_count'];
            $pullRequest->creationDate = self::getDateFromBitbucketString($prData['created_on']);
            $pullRequest->sourceBranch = $prData['source']['branch']['name'];
            $pullRequest->targetBranch = $prData['destination']['branch']['name'];

            $this->prCache[$prHash] = $pullRequest;
        }//end if

        return $this->prCache[$prHash];
    }

    /**
     * Returns the pull request details for a specific PR from the Bitbucket API.
     *
     * @param string $repository The repositor to query.
     * @param int $id The PR id to query.
     * @return mixed[] Detail data provided by the Bitbucket API.
     *
     * @throws \Exception Exception when no details were found in the API.
     */
    private function getPrDetailsFromApi(string $repository, int $id): ?array
    {
        $url = str_replace(['{PROJECT_REPO}', '{ID}'], [$repository, $id], self::PR_DETAILS_URL);
        try {
            $responseJson = $this->client->get($url, ['auth' => $this->authData])->getBody()->getContents();
            $prData = json_decode($responseJson, true);
        } catch (\Exception $e) {
            throw $e;
        }

        return $prData;
    }

    /**
     * Returns the list of issued PRs for the current user
     *
     * @return <string,string>[]
     * @throws \Exception API Exception.
     */
    private function getIssuerPRListFromApi(): array
    {
        $issuerPrList = [];
        $url = str_replace(['{USERNAME}'], [$this->username], self::ISSUED_DATA_URL);
        try {
            $responseJson = $this->client->get($url, ['auth' => $this->authData])->getBody()->getContents();
        } catch (\Exception $e) {
            throw $e;
        }

        $prArray = json_decode($responseJson);

        foreach ($prArray->values as $pullRequest) {
            $issuerPrList[] = [
                'repository' => $pullRequest->source->repository->full_name,
                'id' => $pullRequest->id,
                'url' => $pullRequest->links->html->href,
            ];
        }
        return $issuerPrList;
    }

    /**
     * Returns the list of PRs in which the current user is a reviewer.
     *
     * @param string[] $repositories List of repositories to check for open PRs.
     * @return mixed[]
     * @throws \Exception API Exception.
     */
    private function getReviewerPRListFromApi(array $repositories): array
    {
        $reviewerPrList = [];
        $repositories = array_unique($repositories);

        foreach ($repositories as $repository) {
            $url = str_replace(
                [
                    '{PROJECT_REPO}',
                    '{USERNAME}',
                ],
                [
                    $repository,
                    $this->username,
                ],
                self::REVIEWING_DATA_URL
            );

            try {
                $responseJson = $this->client->get($url, ['auth' => $this->authData])->getBody()->getContents();
            } catch (\Exception $e) {
                throw $e;
            }

            $prArray = json_decode($responseJson);

            foreach ($prArray->values as $pullRequest) {
                $reviewerPrList[] = [
                    'repository' => $pullRequest->source->repository->full_name,
                    'id' => $pullRequest->id,
                    'url' => $pullRequest->links->html->href,
                ];
            }
        }//end foreach

        return $reviewerPrList;
    }


    /**
     * Creates a user from a data array provided by the Bitbucket API.
     *
     * @param mixed[] $data User data fetched by the Bitbucket API.
     * @return User
     */
    private static function getUserFromData(array $data): User
    {
        $user = new User();
        $user->username = $data['username'];
        $user->fullName = $data['display_name'];
        $user->id = $data['account_id'];
        $user->avatar = $data['links']['avatar']['href'];
        return $user;
    }

    /**
     * Creates a DateTime object from a date/time string provided by the Bitbucket API.
     *
     * @param string|null $data Date data fetched by the Bitbucket API.
     * @param string|null $timezone Time zone to be set to the object.
     * @return \DateTime|null
     */
    private static function getDateFromBitbucketString(?string $data, ?string $timezone = 'Europe/Berlin'): ?\DateTime
    {
        if ($data === null) {
            return null;
        }

        return \DateTime::createFromFormat('Y-m-d\TH:i:s.uT', $data)->setTimezone(new \DateTimeZone($timezone));
    }
}
