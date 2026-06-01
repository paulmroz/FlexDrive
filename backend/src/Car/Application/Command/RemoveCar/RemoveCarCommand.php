<?php

declare(strict_types=1);

namespace App\Car\Application\Command\RemoveCar;

use App\Car\Application\Command\RemoveCar\RemoveCarCommandHandler;

/**
 * @see RemoveCarCommandHandler
 */
class RemoveCarCommand
{
    public function __construct(public readonly string $id)
    {
    }
}
