#!/usr/bin/env php
<?php
declare(strict_types=1);

use Bitbar\Repository\RepositoryFactory;
use tyesty\phpitbar\BitbarPlugin;
use tyesty\phpitbar\DropdownLine;
use tyesty\phpitbar\LineParameter;

require_once '.bitbar-pr-overview/vendor/autoload.php';
$username = '';
$password = '';
$repositories = [];

$rep = RepositoryFactory::create(RepositoryFactory::REPO_BITBUCKET, []);
$rep->login($username, $password);

$reviewerList = $rep->getReviewerPullRequestList($repositories);
$issuerList = $rep->getIssuerPullRequestList();

/** @var \Bitbar\PullRequest[] $list */
$list = array_merge($issuerList, $reviewerList);

$plugin = new BitbarPlugin();

$menubar = new \tyesty\phpitbar\MenubarLine(count($list) . ' open PRs');
$plugin->addMenubarLine($menubar);

// Line Parameters.
$smallText = (new LineParameter())->setSize('10');
$smallRedText = (new LineParameter())->setSize('10')->setColor('red');
$smallGreenText = (new LineParameter())->setSize('10')->setColor('green');
$approvedPr = (new LineParameter())->setColor('green');
$notYetApprovedPr = (new LineParameter())->setColor('gray');

// First line.
$firstLineText = ":arrow_forward: " . count($reviewerList) . " PRs for you to review\n:arrow_backward: " . count($issuerList) . " PRs issued by you currently in review";
$firstLine = (new DropdownLine($firstLineText));
$plugin->addDropdownLine($firstLine);
$plugin->addDropdownLine(new DropdownLine('---'));

foreach ($list as $pullRequest) {


    // Headline for PR.
    $prItemText = $pullRequest->title;
    $prItem = (new DropdownLine($prItemText));
    $prItem->setLineParameter((new LineParameter())->setLength(40));

    // Submenu for PR.
    $prSubmenu = new DropdownLine();
    $prSubmenu->setText('Open PR in Browser');
    $prSubmenu->setLineParameter((new LineParameter())->setHref($pullRequest->url));
    $prItem->addChildDropdownLine($prSubmenu);

    $prItem->addChildDropdownLine(new DropdownLine('---'));

    $notYetReviewedByYou = true;

    foreach ($pullRequest->reviewStatus as $reviewStatus) {
        $prSubmenu = new DropdownLine();
        if ($reviewStatus->approved === true) {
            $approved = ':black_small_square:';
            $prSubmenu->setLineParameter($approvedPr);
            if ($reviewStatus->user->username === $username) {
                $notYetReviewedByYou = false;
                $approvalDate = $reviewStatus->lastChanged->format('d.m.Y H:i');
            }
        } else {
            $approved = ':white_small_square:';
            $prSubmenu->setLineParameter($notYetApprovedPr);
        }
        $prSubmenu->setText($approved . ' ' . $reviewStatus->user->fullName);
        $prItem->addChildDropdownLine($prSubmenu);
    }
    $plugin->addDropdownLine($prItem);


    // Detail line for PR.
    $detailText1 = ' :white_small_square: ' . $pullRequest->sourceBranch . ' :arrow_forward: ' . $pullRequest->targetBranch;
    $prDetail1 = (new DropdownLine($detailText1))->setLineParameter($smallText);
    $plugin->addDropdownLine($prDetail1);

    // Detail line 2 for PR.
    $detailText2 = ' :white_small_square: opened by ' . $pullRequest->issuer->fullName . ' on ' . $pullRequest->creationDate->format('d.m.Y H:i');
    $prDetail2 = (new DropdownLine($detailText2))->setLineParameter($smallText);
    $plugin->addDropdownLine($prDetail2);

    // Detail line 4 for PR
    if ($notYetReviewedByYou) {
        $detailText4 = ' :black_small_square: You still have to review this Pull Request!';
        $prDetail4 = (new DropdownLine($detailText4))->setLineParameter($smallRedText);
    } else {
        $detailText4 = ' :black_small_square: Approved by you on ' . $approvalDate;
        $prDetail4 = (new DropdownLine($detailText4))->setLineParameter($smallGreenText);
    }
    $plugin->addDropdownLine($prDetail4);

    // Detail line 3 for PR.
    $detailText3 = ' :white_small_square: :+1:' . $pullRequest->numberOfApproves . ' :bust_in_silhouette:' . $pullRequest->numberOfReviewers . ' :speech_balloon:' . $pullRequest->numberOfComments;
    $prDetail3 = (new DropdownLine($detailText3))->setLineParameter($smallText);
    $plugin->addDropdownLine($prDetail3);


    $plugin->addDropdownLine(new DropdownLine('---'));
}

// Last line.
$lastItem = new DropdownLine('Refresh list...');
$lastItem->setLineParameter((new LineParameter())->setIsRefreshLine(true));
$plugin->addDropdownLine($lastItem);

echo $plugin->render();