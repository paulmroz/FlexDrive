<?php

declare(strict_types=1);

namespace App\Car\Infrastructure\Controller;

use App\Car\Application\Command\ProcessChatMessageCommand;
use App\Car\Infrastructure\Request\SubmitMessageRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Redis;
use RedisException;

class AdvisorChatController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private Redis $redis
    ) {
    }

    #[Route(path: '/api/cars/advisor/chat/{sessionId}', name: 'api_cars_advisor_chat', methods: ['POST'])]
    public function submit(string $sessionId, #[MapRequestPayload] SubmitMessageRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(new ProcessChatMessageCommand(
            sessionId: $sessionId,
            messageContent: $request->message
        ));

        return new JsonResponse(data: null, status: Response::HTTP_ACCEPTED);
    }

    #[Route(path: '/api/cars/advisor/sse/{sessionId}', name: 'api_cars_advisor_sse', methods: ['GET'])]
    public function streamMessages(string $sessionId): StreamedResponse
    {
        $response = new StreamedResponse(callback: function () use ($sessionId): void {
            header(header: 'Content-Type: text/event-stream');
            header(header: 'Cache-Control: no-cache');
            header(header: 'Connection: keep-alive');
            header(header: 'X-Accel-Buffering: no');

            $this->redis->setOption(option: Redis::OPT_READ_TIMEOUT, value: 45);

            try {
                $this->redis->subscribe(
                    channels: ['chat.' . $sessionId],
                    callback: function (Redis $redis, string $channel, string $message): void {
                        echo 'data: ' . $message . "\n\n";
                        ob_flush();
                        flush();
                        $redis->unsubscribe();
                    }
                );
            } catch (RedisException $exception) {
                echo "event: timeout\ndata: Connection timed out\n\n";
                ob_flush();
                flush();
            }
        });

        return $response;
    }
}
