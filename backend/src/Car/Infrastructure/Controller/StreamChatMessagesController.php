<?php

declare(strict_types=1);

namespace App\Car\Infrastructure\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Redis;
use RedisException;

class StreamChatMessagesController extends AbstractController
{
    public function __construct(
        private Redis $redis
    ) {
    }

    #[Route(path: '/api/cars/advisor/sse/{sessionId}', name: 'api_cars_advisor_sse', methods: ['GET'])]
    public function __invoke(string $sessionId): StreamedResponse
    {
        $response = new StreamedResponse(callbackOrChunks: function () use ($sessionId): void {
            set_time_limit(seconds: 0);

            header(header: 'Content-Type: text/event-stream');
            header(header: 'Cache-Control: no-cache');
            header(header: 'Connection: keep-alive');
            header(header: 'X-Accel-Buffering: no');

            $this->redis->setOption(option: Redis::OPT_READ_TIMEOUT, value: 45);

            $existingMessage = $this->redis->get(key: 'chat_result.' . $sessionId);
            if ($existingMessage !== false) {
                echo 'data: ' . $existingMessage . "\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
                $this->redis->del(key: 'chat_result.' . $sessionId);
                return;
            }

            try {
                $this->redis->subscribe(
                    channels: ['chat.' . $sessionId],
                    cb: function (Redis $redis, string $channel, string $message) use ($sessionId): void {
                        echo 'data: ' . $message . "\n\n";
                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();
                        $redis->del(key: 'chat_result.' . $sessionId);
                        $redis->unsubscribe();
                    }
                );
            } catch (RedisException $exception) {
                echo "event: timeout\ndata: Connection timed out\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
        });

        return $response;
    }
}
