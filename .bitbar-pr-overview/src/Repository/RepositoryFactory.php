<?php
declare(strict_types=1);

namespace Bitbar\Repository;

/**
 * Repository Connector factory.
 *
 * @package Bitbar\Repository
 */
class RepositoryFactory
{

    public const REPO_BITBUCKET = 'bitbucket';

    public const REPO_GITHUB = 'github';

    /**
     * Repository Connector factory method.
     *
     * Returns the proper repository connector.
     *
     * @param string $type Type of repository to connect to.
     * @param string[] $config Configuration values.
     * @return BitbucketRepository|GithubAbstractRepository
     * @throws \Exception Thrown if type is invalid.
     */
    public static function create(string $type, array $config)
    {
        switch (strtolower($type)) {
            case self::REPO_BITBUCKET:
                return new BitbucketRepository($config);

            case self::REPO_GITHUB:
                return new GithubAbstractRepository($config);

            default:
                throw new \Exception('Repository type {$type} is not defined.');
        }
    }

    /**
     * Private RepositoryFactory constructor.
     *
     * Prevents factory from being instantiated
     */
    private function __construct()
    {
    }
}
