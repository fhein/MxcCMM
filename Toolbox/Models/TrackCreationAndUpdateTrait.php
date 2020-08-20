<?php /** @noinspection PhpUnhandledExceptionInspection */


namespace MxcCommons\Toolbox\Models;

use DateTime;
use DateTimeInterface;
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

    public function setCreated($created) {
        if ($created instanceof DateTimeInterface) {
            $this->created = $created;
        } elseif ($created !== null) {
            // throws on error
            $this->created = new DateTime($created);
        }
    }

    /**
     * @return DateTime
     */
    public function getUpdated(): DateTime
    {
        return $this->updated;
    }

    public function setUpdated($updated) {
        if ($updated instanceof DateTimeInterface) {
            $this->updated = $updated;
        } elseif ($updated !== null) {
            // throws on error
            $this->updated = new DateTime($updated);
        }
    }
}