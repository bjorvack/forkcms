<?php

namespace Backend\Modules\Profiles\Domain\ProfileSession;

use Backend\Modules\Profiles\Domain\Profile\Profile;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="ProfilesProfileSession")
 * @ORM\Entity(repositoryClass="Backend\Modules\Profiles\Domain\ProfileSession\ProfileSessionRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ProfileSession
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="string", length=50)
     */
    private $sessionId;

    /**
     * @var Profile
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Backend\Modules\Profiles\Domain\Profile\Profile")
     */
    private $profile;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $secretKey;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $date;

    public function __construct(string $sessionId, Profile $profile, ?string $secretKey)
    {
        $this->sessionId = $sessionId;
        $this->profile = $profile;
        $this->secretKey = $secretKey;
    }

    public function updateDate(): void
    {
        $this->date = new DateTime();
    }

    public function updateSecretKey(string $sessionId, string $secretKey): void
    {
        $this->sessionId = $sessionId;
        $this->secretKey = $secretKey;
        $this->updateDate();
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function getProfile(): Profile
    {
        return $this->profile;
    }

    public function getSecretKey(): ?string
    {
        return $this->secretKey;
    }

    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist(): void
    {
        $this->date = new DateTime();
    }
}
