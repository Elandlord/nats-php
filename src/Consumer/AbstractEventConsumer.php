<?php
declare(strict_types=1);

namespace Elandlord\NatsPhp\Consumer;

use Basis\Nats\Message\Msg;
use Elandlord\NatsPhp\Connection\NatsConnection;
use Elandlord\NatsPhp\Contract\Consumer\EventConsumerInterface;
use Elandlord\NatsPhp\Contract\Handler\EventHandlerInterface;
use Elandlord\NatsPhp\Exception\InvalidEventEnvelopeException;
use Elandlord\NatsPhp\Messaging\EventEnvelope;
use Exception;
use Throwable;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
abstract class AbstractEventConsumer implements EventConsumerInterface
{
    public const ALLOWED_CLASSES_KEY = 'allowed_classes';

    /** @var array<string, EventHandlerInterface> */
    protected array $handlerMap;

    /**
     * @param EventHandlerInterface[] $handlers
     */
    public function __construct(
        protected readonly NatsConnection $connection,
        protected readonly array          $handlers,
        protected readonly string         $streamName,
        protected readonly string         $consumerName
    )
    {
        $this->handlerMap = $this->buildHandlerMap($handlers);
    }

    /**
     * @throws Exception
     */
    public function consume(): void
    {
        $client = $this->connection->getClient();
        $stream = $client->getApi()->getStream($this->streamName);

        $consumer = $stream->getConsumer($this->consumerName);
        $queue = $consumer->getQueue();

        while ($message = $queue->next()) {
            $this->processMessage($message);
        }
    }

    protected function processMessage(Msg $message): void
    {
        try {
            if (!$this->shouldProcess($message)) {
                return;
            }

            $envelope = $this->extractEnvelope($message);

            $handler = $this->resolveHandler($envelope);
            if ($handler === null) {
                return;
            }

            $handler->handle($envelope->body);
            $message->ack();

        } catch (Throwable $exception) {
            $message->nack(1.0);
            $this->onProcessingError($exception, $message);
        }
    }

    protected function shouldProcess(Msg $message): bool
    {
        return $message->replyTo !== null;
    }

    protected function extractEnvelope(Msg $message): EventEnvelope
    {
        $raw = $message->payload->body;

        $envelope = $this->deserializeEnvelope($raw);

        if ($envelope instanceof EventEnvelope) {
            return $envelope;
        }

        throw new InvalidEventEnvelopeException();
    }

    protected function onProcessingError(Throwable $exception, Msg $message): void
    {
        // Possible to override by subclasses
    }

    protected function resolveHandler(EventEnvelope $envelope): ?EventHandlerInterface
    {
        return $this->handlerMap[$envelope->eventName] ?? null;
    }

    protected function deserializeEnvelope(string $envelope): ?EventEnvelope
    {
        return unserialize($envelope, [
            self::ALLOWED_CLASSES_KEY => [EventEnvelope::class]
        ]);
    }

    /**
     * @param EventHandlerInterface[] $handlers
     * @return array<string, EventHandlerInterface>
     */
    protected function buildHandlerMap(array $handlers): array
    {
        $map = [];
        foreach ($handlers as $handler) {
            $map[$handler->getHandledEventName()] = $handler;
        }
        return $map;
    }
}
