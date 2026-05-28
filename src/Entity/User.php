<?php

namespace App\Entity;


use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`users`')]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé.')]
#[UniqueEntity(fields: ['username'], message: 'Ce nom d\'utilisateur est déjà pris.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $fullName = null;

    #[ORM\Column(type: Types::TEXT, unique: true)]
    private ?string $username = null;

    #[ORM\Column(type: Types::TEXT, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $major = null;

    #[ORM\Column(name: 'password_hash', type: Types::TEXT)]
    private ?string $password = null;

    #[ORM\Column(type: Types::TEXT, options: ['default' => 'student'])]
    private string $role = 'student';

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // --- GETTERS & SETTERS ---
    public function getId(): ?string { return $this->id; }

    public function getFullName(): ?string { return $this->fullName; }
    public function setFullName(string $fullName): static { $this->fullName = $fullName; return $this; }

    public function getUsername(): ?string { return $this->username; }
    public function setUsername(string $username): static { $this->username = $username; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getMajor(): ?string { return $this->major; }
    public function setMajor(string $major): static { $this->major = $major; return $this; }

    public function getRole(): string { return $this->role; }
    public function setRole(string $role): static { $this->role = $role; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }

    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    // --- METHODES DE SECURITE SYMFONY ---
    /**
     * Un identifiant visuel qui représente l'utilisateur (utilisé par Symfony).
     */
    public function getUserIdentifier(): string { return (string) $this->username; }
    /**
     * Gère la hiérarchie des rôles.
     */
    public function getRoles(): array { return array_unique(['ROLE_USER', 'ROLE_' . strtoupper($this->role)]); }
    /**
     * Si on stocke des données sensibles temporaires sur l'objet, on les nettoie ici.
     * En général, on la laisse vide.
     */
    public function eraseCredentials(): void {}

}
