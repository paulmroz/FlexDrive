<?php

declare(strict_types=1);

namespace App\Car\Tests\Unit;

use App\Car\Domain\ValueObject\VibePrompt;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

class VibePromptTest extends TestCase
{
    public function testValidPromptIsAccepted(): void
    {
        $prompt = new VibePrompt(value: 'I want a fast car');
        $this->assertEquals(expected: 'I want a fast car', actual: $prompt->getValue());
    }

    public function testEmptyPromptThrowsException(): void
    {
        $this->expectException(exception: InvalidArgumentException::class);
        new VibePrompt(value: '   ');
    }

    public function testTooLongPromptThrowsException(): void
    {
        $this->expectException(exception: InvalidArgumentException::class);
        new VibePrompt(value: str_repeat(string: 'a', times: 501));
    }
}
