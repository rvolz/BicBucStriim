<?php
declare(strict_types=1);

namespace App\Domain\User;
class User
{
    /**
     * @var int|null
     */
    private $id;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $languages;

    /**
     * @var string
     */
    private $tags;

    /**
     * @var int
     */
    private $role;

    /**
     * @param int|null $id
     * @param string $username
     * @param string $password
     * @param string $email
     * @param string $languages
     * @param string $tags
     * @param int $role
     */
    public function __construct(int $id, string $username, string $password, ?string $email, ?string $languages, ?string $tags, int $role)
    {
        $this->id = $id;
        $this->username = $username;
        $this->password = $password;
        $this->email = $email;
        $this->languages = $languages;
        $this->tags = $tags;
        $this->role = $role;
    }

    /**
     * @return bool
     */
    public function isAdmin(): bool {
        return $this->role == 1;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getLanguages(): ?string
    {
        return $this->languages;
    }

    /**
     * @param string $languages
     */
    public function setLanguages(string $languages): void
    {
        $this->languages = $languages;
    }

    /**
     * @return string
     */
    public function getTags(): ?string
    {
        return $this->tags;
    }

    /**
     * @param string $tags
     */
    public function setTags(string $tags): void
    {
        $this->tags = $tags;
    }

    /**
     * @return int
     */
    public function getRole(): int
    {
        return $this->role;
    }

    /**
     * @param int $role
     */
    public function setRole(int $role): void
    {
        $this->role = $role;
    }


}
