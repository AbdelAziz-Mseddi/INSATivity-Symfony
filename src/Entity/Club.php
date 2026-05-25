<?php

namespace App\Entity;

use App\Repository\ClubRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClubRepository::class)]
#[ORM\Table(name: '`clubs`')]
#[ORM\Index(name: 'idx_clubs_category', columns: ['category'])]
class Club
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING)]
    private ?string $id = null; // Ex: 'acm', 'ieee'

    #[ORM\Column(type: Types::TEXT, unique: true)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $category = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $logo = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $banner = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $updatedAt = null;

    // Relation One-To-Many avec les Events
    #[ORM\OneToMany(mappedBy: 'club', targetEntity: Event::class, cascade: ['remove'])]
    private Collection $events;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTime();
        $this->events = new ArrayCollection();
    }

    // --- GETTERS & SETTERS ---
    public function getId(): ?string { return $this->id; }
    public function setId(string $id): static { $this->id = $id; return $this; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getCategory(): ?string { return $this->category; }
    public function setCategory(string $category): static { $this->category = $category; return $this; }

    public function getLogo(): ?string { return $this->logo; }
    public function setLogo(string $logo): static { $this->logo = $logo; return $this; }

    public function getBanner(): ?string { return $this->banner; }
    public function setBanner(string $banner): static { $this->banner = $banner; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; return $this; }

    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeInterface $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }

    /**
     * @return Collection<int, Event>
     */
    public function getEvents(): Collection { return $this->events; }
}
