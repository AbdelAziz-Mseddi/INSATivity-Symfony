<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: '`events`')]
#[ORM\Index(name: 'idx_events_event_date', columns: ['event_date'])]
#[ORM\Index(name: 'idx_events_featured', columns: ['featured'])]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: Club::class, inversedBy: 'events')]
    #[ORM\JoinColumn(name: 'club_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Club $club = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $image = null;

    #[ORM\Column(name: 'event_date', type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $eventDate = null;

    #[ORM\Column(name: 'event_time', type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $eventTime = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $location = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $participants = 0;

    #[ORM\Column(name: 'max_participants', type: Types::INTEGER, options: ['default' => 0])]
    private int $maxParticipants = 0;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $featured = false;

    #[ORM\Column(name: 'is_approved', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isApproved = false;

    #[ORM\Column(name: 'created_at', type: Types::DATETIMETZ_IMMUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIMETZ_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $tags = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTime();
    }

    // --- GETTERS & SETTERS ---

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getClub(): ?Club
    {
        return $this->club;
    }

    public function setClub(?Club $club): static
    {
        $this->club = $club;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getEventDate(): ?\DateTimeInterface
    {
        return $this->eventDate;
    }

    public function setEventDate(\DateTimeInterface $eventDate): static
    {
        $this->eventDate = $eventDate;
        return $this;
    }

    public function getEventTime(): ?\DateTimeInterface
    {
        return $this->eventTime;
    }

    public function setEventTime(\DateTimeInterface $eventTime): static
    {
        $this->eventTime = $eventTime;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getParticipants(): int
    {
        return $this->participants;
    }

    public function setParticipants(int $participants): static
    {
        $this->participants = $participants;
        return $this;
    }

    public function getMaxParticipants(): int
    {
        return $this->maxParticipants;
    }

    public function setMaxParticipants(int $maxParticipants): static
    {
        $this->maxParticipants = $maxParticipants;
        return $this;
    }

    public function isFeatured(): bool
    {
        return $this->featured;
    }

    public function setFeatured(bool $featured): static
    {
        $this->featured = $featured;
        return $this;
    }

    public function isApproved(): bool
    {
        return $this->isApproved;
    }

    public function setIsApproved(bool $isApproved): static
    {
        $this->isApproved = $isApproved;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function setTags(?array $tags): static
    {
        $this->tags = $tags;
        return $this;
    }

    // --- LOGIQUE METIER TRANSFEREE ---

    /**
     * Remplace la méthode getEventStatus() de l'ancien modèle.
     * Calcule dynamiquement le statut sans le stocker inutilement en base.
     */
    public function getStatus(): string
    {
        $now = new \DateTime();

        if ($this->eventDate === null || $this->eventTime === null) {
            return 'finished';
        }

        $eventDateTime = \DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $this->eventDate->format('Y-m-d') . ' ' . $this->eventTime->format('H:i:s')
        );

        return $eventDateTime > $now ? 'published' : 'finished';
    }
}
