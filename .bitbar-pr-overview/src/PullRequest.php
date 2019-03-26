<?php
declare(strict_types=1);

namespace Bitbar;

class PullRequest
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $url;

    /**
     * @var string
     */
    public $repository;

    /**
     * @var string
     */
    public $sourceBranch;

    /**
     * @var string
     */
    public $targetBranch;

    /**
     * @var User
     */
    public $issuer;

    /**
     * @var User[]
     */
    public $reviewers;

    /**
     * @var ReviewStatus[]
     */
    public $reviewStatus;

    /**
     * @var int
     */
    public $numberOfComments;

    /**
     * @var int
     */
    public $numberOfApproves = 0;

    /**
     * @var int
     */
    public $numberOfReviewers = 0;

    /**
     * @var \DateTime
     */
    public $creationDate;
}
