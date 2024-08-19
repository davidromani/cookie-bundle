<?php

namespace Eveltic\CookieBundle\Service;

use DateTime;
use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\EntityManagerInterface;
use Eveltic\CookieBundle\Entity\UserCookieConsent;

/**
 * Service responsible for storing and retrieving cookie consent data in the database.
 */
class CookieConsentStorageService
{
    /**
     * Constructor for the CookieConsentStorageService class.
     *
     * @param EntityManagerInterface $entityManager 
     *        The Doctrine Entity Manager used for interacting with the database.
     */
    public function __construct(
        private EntityManagerInterface $entityManager
        )
    {}

    /**
     * Saves the user's cookie consent data to the database.
     *
     * @param Uuid $uuid 
     *        A universally unique identifier (UUID) for the user.
     *
     * @param array $consentData 
     *        An array containing the user's consent decisions for various cookie categories.
     *
     * @param DateTime $dateTime 
     *        A DateTime object containing the user's consent date time of the consenting.
     *
     * @param DateTime $expirationDate 
     *        A DateTime object containing the user's expiration consent date time.
     *
     * @param ?string $ip 
     *        The IP address of the user, which can be anonymized or null.
     *
     * @param int $version 
     *        The version of the cookie consent configuration that the user has agreed to.
     *
     * @param ?string $userAgent 
     *        The user agent string of the user's browser, or null if not available.
     *
     * @return void
     */
    public function saveConsent(Uuid $uuid, array $consentData, DateTime $dateTime, DateTime $expirationDate, ?string $ip, int $version, ?string $userAgent): void
    {
        $consent = new UserCookieConsent();
        $consent->setUuid($uuid);
        $consent->setConsentData($consentData);
        $consent->setConsentDate($dateTime);
        $consent->setExpirationDate($expirationDate);
        $consent->setIp($ip);
        $consent->setUserAgent($userAgent);
        $consent->setVersion($version);

        $this->entityManager->persist($consent);
        $this->entityManager->flush();
    }
}
