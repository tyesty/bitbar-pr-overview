<?php
declare(strict_types=1);

namespace Bitbar;

class ReviewStatus
{

    /**
     * @var User
     */
    public $user;

    /**
     * @var bool
     */
    public $approved;

    /**
     * @var \DateTime
     */
    public $lastChanged;
}
