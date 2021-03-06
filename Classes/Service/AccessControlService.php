<?php

/*
 * This file is part of the package thucke/th-rating.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

/** @noinspection PhpFullyQualifiedNameUsageInspection */
namespace Thucke\ThRating\Service;

use Thucke\ThRating\Domain\Model\Voter;
use Thucke\ThRating\Exception\FeUserNotFoundException;
use TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * An access control service
 *
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU protected License, version 2
 */
class AccessControlService extends AbstractExtensionService
{
    /**
     * @var \TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository $frontendUserRepository
     */
    protected $frontendUserRepository;

    /**
     * @param \TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository $frontendUserRepository
     * @noinspection PhpUnused
     */
    public function injectFrontendUserRepository(FrontendUserRepository $frontendUserRepository): void
    {
        $this->frontendUserRepository = $frontendUserRepository;
    }

    /**
     * @var \Thucke\ThRating\Domain\Repository\VoterRepository $voterRepository
     */
    protected $voterRepository;

    /**
     * @param \Thucke\ThRating\Domain\Repository\VoterRepository $voterRepository
     */
    public function injectVoterRepository(\Thucke\ThRating\Domain\Repository\VoterRepository $voterRepository): void
    {
        $this->voterRepository = $voterRepository;
    }

    /**
     * @var \TYPO3\CMS\Core\Context\Context $context
     */
    protected $context;
    /**
     * @param \TYPO3\CMS\Core\Context\Context $context
     */
    public function injectContext(\TYPO3\CMS\Core\Context\Context $context): void
    {
        $this->context = $context;
    }

    /**
     * Tests, if the given person is logged into the frontend
     *
     * @param \TYPO3\CMS\Extbase\Domain\Model\FrontendUser|null $person The person
     * @return bool    The result; true if the given person is logged in; otherwise false
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    public function isLoggedIn(\TYPO3\CMS\Extbase\Domain\Model\FrontendUser $person = null): bool
    {
        if (is_object($person)) {
            if ($person->getUid() &&
                    ($person->getUid() === $this->getFrontendUserUid())) {
                return true; //treat anonymous user also as logged in
            }
        }
        return false;
    }

    /**
     * @return bool
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    public function backendAdminIsLoggedIn(): bool
    {
        return $this->context->getPropertyFromAspect('backend.user', 'isLoggedIn');
    }

    /**
     * @return bool
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    public function hasLoggedInFrontendUser(): bool
    {
        return $this->context->getPropertyFromAspect('frontend.user', 'isLoggedIn');
    }

    /**
     * @return array
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    public function getFrontendUserGroups(): array
    {
        if ($this->hasLoggedInFrontendUser()) {
            return $this->context->getPropertyFromAspect('frontend.user', 'groupIds');
        }
        return [];
    }

    /**
     * @return int|null
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    public function getFrontendUserUid(): ?int
    {
        if ($this->hasLoggedInFrontendUser()) {
            return $this->context->getPropertyFromAspect('frontend.user', 'id');
        }
        return null;
    }

    /**
     * Loads objects from repositories
     *
     * @param mixed $voter
     * @return \TYPO3\CMS\Extbase\Domain\Model\FrontendUser
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    public function getFrontendUser($voter = null): ?\TYPO3\CMS\Extbase\Domain\Model\FrontendUser
    {
        //set userobject
        if (!$voter instanceof \TYPO3\CMS\Extbase\Domain\Model\FrontendUser) {
            //TODO Errorhandling if no user is logged in
            if ((int)$voter === 0) {
                //get logged in fe-user
                $voter = $this->frontendUserRepository->findByUid($this->getFrontendUserUid());
            } else {
                $voter = $this->frontendUserRepository->findByUid((int)$voter);
            }
        }
        return $voter;
    }

    /**
     * Loads objects from repositories
     *
     * @param int|null $voter
     * @return \Thucke\ThRating\Domain\Model\Voter
     * @throws FeUserNotFoundException
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    public function getFrontendVoter(?int $voter = 0): Voter
    {
        $exceptionMessageArray = [];

        /** @var Voter $voterObject */
        $voterObject = null;

        //TODO Errorhandling if no user is logged in
        if ((int)$voter === 0) {
            //get logged in fe-user
            $voterObject = $this->voterRepository->findByUid($this->getFrontendUserUid());
            $exceptionMessageArray = [$this->getFrontendUserUid()];
            $exceptionMessageType = 'feUser';
        } else {
            $voterObject = $this->voterRepository->findByUid((int)$voter);
            $exceptionMessageArray = [(int)$voter];
            $exceptionMessageType = 'anonymousUser';
        }

        if (empty($voterObject)) {
            throw new FeUserNotFoundException(
                LocalizationUtility::translate(
                    'flash.pluginConfiguration.missing.' . $exceptionMessageType,
                    'ThRating',
                    $exceptionMessageArray
                ),
                1602095329
            );
        }

        return $voterObject;
    }
}
