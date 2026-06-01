<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\UserId;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: '`users`')]
class User
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string')]
    private string $password;

    /** @var array<string> */
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    /**
     * @param array<string> $roles
     */
    public function __construct(
        UserId $id,
        Email $email,
        string $password,
        array $roles = ['ROLE_USER']
    ) {
        $this->id = $id->getValue();
        $this->email = $email->getValue();
        $this->password = $password;
        $this->roles = $roles;
    }

    public function getId(): UserId
    {
        return new UserId(value: $this->id);
    }

    public function getEmail(): Email
    {
        return new Email(value: $this->email);
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array: array_unique(array: $roles));
    }
}
