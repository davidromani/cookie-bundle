<?php

namespace Eveltic\CookieBundle\Entity;

use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;

#[ORM\Entity]
#[ORM\Table(name: "user_cookie_consent")]
#[ORM\Index(name: "uuid_idx", columns: ["uuid"])]
class UserCookieConsent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "json")]
    private array $consentData = [];

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $consentDate;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $expirationDate;

    #[ORM\Column(type: "string", length: 45, nullable: true)]
    private ?string $ip = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private string $userAgent;

    #[ORM\Column(type: UuidType::NAME, nullable: false)]
    private ?Uuid $uuid = null;

    #[ORM\Column(type: "integer")]
    private ?int $version = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConsentData(): array
    {
        return $this->consentData;
    }

    public function setConsentData(array $consentData): self
    {
        $this->consentData = $consentData;
        return $this;
    }

    public function getConsentDate(): \DateTimeInterface
    {
        return $this->consentDate;
    }

    public function setConsentDate(\DateTimeInterface $consentDate): self
    {
        $this->consentDate = $consentDate;
        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): self
    {
        $this->ip = $ip;
        return $this;
    }

    public function getUserAgent()
    {
        return $this->userAgent;
    }

    public function setUserAgent($userAgent)
    {
        $this->userAgent = substr($userAgent, 0, 255);
        return $this;
    }

    public function getUuid(): ?Uuid
    {
        return $this->uuid;
    }

    public function setUuid(?Uuid $uuid): self
    {
        $this->uuid = $uuid;
        return $this;
    }

    public function getVersion(): ?int
    {
        return $this->version;
    }

    public function setVersion($version): self
    {
        $this->version = $version;
        return $this;
    }

    public function getExpirationDate(): \DateTimeInterface
    {
        return $this->expirationDate;
    }

    public function setExpirationDate($expirationDate): self
    {
        $this->expirationDate = $expirationDate;
        return $this;
    }
}
