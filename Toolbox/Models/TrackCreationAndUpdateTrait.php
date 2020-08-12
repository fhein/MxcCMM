<?php /** @noinspection PhpUnhandledExceptionInspection */


namespace MxcCommons\Toolbox\Models;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass
 */
trait TrackCreationAndUpdateTrait
{
    /**
     * @var DateTime $created
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $created = null;

    /**
     * @var DateTime $updated
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $updated = null;

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updateTimestamps() {
        $now = new DateTime();
        $this->updated = $now;
        if ( null === $this->created) {
            $this->created = $now;
        }
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    /**
     * @return DateTime
     */
    public function getUpdated(): DateTime
    {
        return $this->updated;
    }
}