<?php

declare(strict_types=1);

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HealthCheckTest extends WebTestCase
{
    public function testHealthCheckReturnsSuccess(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/health');
        $this->assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content);
        $data = json_decode(json: $content, associative: true);
        $this->assertEquals('UP', $data['status']);
        $this->assertEquals('OK', $data['database']);
    }
}
