<?php
declare(strict_types=1);

namespace Bitbar\Repository;

use Bitbar\PullRequest;

/**
 * Abstract repository interface
 *
 * @package Bitbar\Repository
 */
abstract class AbstractRepository
{

    /**
     * Configuration array
     *
     * @var string[]
     */
    protected $config = [];

    /**
     * AbstractRepository constructor.
     *
     * Sets the configuration array.
     *
     * @param string[] $config Configuration array that holds everything needed to connect to the repository.
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Logs in to the repository.
     *
     * @param string $username The username used for logging in.
     * @param string $password The password used for logging in.
     * @return void
     */
    abstract public function login(string $username, string $password): void;

    /**
     * Returns a list of pull requests for a specific ISSUER.
     *
     * This method returns a list of standardized PullRequest objects.
     *
     * @return PullRequest[]|null
     */
    abstract public function getIssuerPullRequestList(): ?array;

    /**
     * Returns a list of pull requests for a specific REVIEWER.
     *
     * This method returns a list of standardized PullRequest objects.
     *
     * @param string[] $repositoryList The list of repositories to search for Pull Requests.
     * @return PullRequest[]|null
     */
    abstract public function getReviewerPullRequestList(array $repositoryList): ?array;

    /**
     * Returns the details of a single pull request.
     *
     * @param string $repository The repository where the pull requests is located.
     * @param int $id The unique identifier of the pull request.
     * @return PullRequest
     */
    abstract public function getPullRequestDetails(string $repository, int $id): PullRequest;
}
