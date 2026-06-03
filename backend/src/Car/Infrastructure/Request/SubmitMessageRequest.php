<?php

declare(strict_types=1);

namespace App\Car\Infrastructure\Request;

use Symfony\Component\Validator\Constraints as Assert;

class SubmitMessageRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 500)]
        public string $message
    ) {
    }
}
