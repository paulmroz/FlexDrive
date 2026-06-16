<?php

declare(strict_types=1);

namespace App\Car\Infrastructure\EventListener;

use Symfony\AI\Platform\Event\InvocationEvent;
use Symfony\AI\Platform\Event\ResultEvent;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Message\SystemMessage;
use Symfony\AI\Platform\Bridge\DeepSeek\DeepSeek;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Throwable;

class DeepSeekStructuredOutputListener
{
    #[AsEventListener(event: InvocationEvent::class, priority: -10)]
    public function onInvocation(InvocationEvent $event): void
    {
        file_put_contents(filename: 'var/listener_invocation_runs.txt', data: 'invocation runs');

        $model = $event->getModel();
        if (!$model instanceof DeepSeek) {
            return;
        }

        $options = $event->getOptions();
        if (!isset($options['response_format'])) {
            return;
        }

        $schema = null;
        if (isset($options['response_format']['json_schema']['schema'])) {
            $schema = $options['response_format']['json_schema']['schema'];
        }

        // DeepSeek doesn't support json_schema type, only json_object
        $options['response_format'] = ['type' => 'json_object'];
        $event->setOptions(options: $options);

        // DeepSeek JSON mode requires the word "json" to be in the prompt
        $input = $event->getInput();
        if ($input instanceof MessageBag) {
            $systemMessage = $input->getSystemMessage();
            if ($systemMessage !== null) {
                $content = (string) $systemMessage->getContent();
                $newContent = $content . "\n\nReturn the output as a JSON object.";
                if ($schema !== null) {
                    $schemaJson = json_encode(value: $schema, flags: JSON_PRETTY_PRINT);
                    $newContent .= "\n\nYou MUST format your response as a JSON object strictly matching this JSON schema:\n" . $schemaJson;
                }
                $newInput = $input->withSystemMessage(message: new SystemMessage($newContent));
                $event->setInput(input: $newInput);
            }
        }
    }

    #[AsEventListener(event: ResultEvent::class)]
    public function onResult(ResultEvent $event): void
    {
        file_put_contents(filename: 'var/listener_result_runs.txt', data: 'result runs');

        $model = $event->getModel();
        if (!$model instanceof DeepSeek) {
            return;
        }

        try {
            $data = $event->getDeferredResult()->getRawResult()->getData();
            file_put_contents(filename: 'var/deepseek_raw.json', data: json_encode(value: $data, flags: JSON_PRETTY_PRINT));
        } catch (Throwable $e) {
            file_put_contents(filename: 'var/listener_error.txt', data: $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }
}
