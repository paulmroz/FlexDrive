<?php

declare(strict_types=1);

namespace App\Car\Domain\Entity;

use App\Car\Domain\ValueObject\CarId;
use Doctrine\ORM\Mapping as ORM;
use DomainException;

#[ORM\Entity]
#[ORM\Table(name: '`cars`')]
class Car
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 100)]
    private string $brand;

    #[ORM\Column(type: 'string', length: 100)]
    private string $model;

    #[ORM\Column(type: 'integer')]
    private int $pricePerDay;

    #[ORM\Column(type: 'boolean')]
    private bool $available;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $lockedBySaga = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lockExpiresAt = null;

    public function __construct(
        CarId $id,
        string $brand,
        string $model,
        int $pricePerDay,
        bool $available = true
    ) {
        $this->id = $id->getValue();
        $this->brand = $brand;
        $this->model = $model;
        $this->pricePerDay = $pricePerDay;
        $this->available = $available;
    }

    public function getId(): CarId
    {
        return new CarId(value: $this->id);
    }

    public function getBrand(): string
    {
        return $this->brand;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getPricePerDay(): int
    {
        return $this->pricePerDay;
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function getLockedBySaga(): ?string
    {
        return $this->lockedBySaga;
    }

    public function getLockExpiresAt(): ?\DateTimeImmutable
    {
        return $this->lockExpiresAt;
    }

    public function book(?string $sagaId = null): void
    {
        if (false === $this->available) {
            throw new DomainException(message: 'Car is already booked/unavailable.');
        }
        $this->available = false;
        $this->lockedBySaga = $sagaId;
        if (null !== $sagaId) {
            $this->lockExpiresAt = new \DateTimeImmutable(datetime: '+5 minutes');
        }
    }

    public function release(): void
    {
        $this->available = true;
        $this->lockedBySaga = null;
        $this->lockExpiresAt = null;
    }

    public function update(
        string $brand,
        string $model,
        int $pricePerDay,
        bool $available
    ): void {
        $this->brand = $brand;
        $this->model = $model;
        $this->pricePerDay = $pricePerDay;
        $this->available = $available;
    }
}
