<?php

declare(strict_types=1);

namespace App\User\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class RegisterUserAndLoginWorkflowTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $container = static::getContainer();
        $entityManager = $container->get(id: 'doctrine.orm.entity_manager');
        $connection = $entityManager->getConnection();

        $connection->executeStatement(sql: 'TRUNCATE TABLE "users" CASCADE');
    }

    public function testUserCanBeRegisteredSuccessfully(): void
    {
        $this->client->request(
            method: 'POST',
            uri: '/api/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode(value: [
                'email' => 'newuser@example.com',
                'password' => 'SecureP@ssword123!'
            ])
        );

        $this->assertSame(
            Response::HTTP_CREATED,
            $this->client->getResponse()->getStatusCode()
        );

        $content = $this->client->getResponse()->getContent();
        $this->assertNotFalse($content);
        $data = json_decode(
            json: $content,
            associative: true
        );

        $this->assertSame(
            'User registered successfully.',
            $data['message']
        );
    }

    public function testRegistrationFailsWithInvalidPayload(): void
    {
        $this->client->request(
            method: 'POST',
            uri: '/api/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode(value: [
                'email' => 'invalid-email',
                'password' => 'short'
            ])
        );

        $this->assertSame(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $this->client->getResponse()->getStatusCode()
        );
    }

    public function testRegisteredUserCanLoginAndObtainJwtToken(): void
    {
        $email = 'loginuser@example.com';
        $password = 'SecureP@ssword123!';

        $this->client->request(
            method: 'POST',
            uri: '/api/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode(value: [
                'email' => $email,
                'password' => $password
            ])
        );

        $this->client->request(
            method: 'POST',
            uri: '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode(value: [
                'email' => $email,
                'password' => $password
            ])
        );

        $this->assertSame(
            Response::HTTP_OK,
            $this->client->getResponse()->getStatusCode()
        );

        $content = $this->client->getResponse()->getContent();
        $this->assertNotFalse($content);
        $data = json_decode(
            json: $content,
            associative: true
        );

        $this->assertArrayHasKey('token', $data);
        $this->assertNotEmpty($data['token']);
    }

    public function testLoginFailsWithInvalidCredentials(): void
    {
        $this->client->request(
            method: 'POST',
            uri: '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode(value: [
                'email' => 'nonexistent@example.com',
                'password' => 'wrongpassword'
            ])
        );

        $this->assertSame(
            Response::HTTP_UNAUTHORIZED,
            $this->client->getResponse()->getStatusCode()
        );
    }
}
