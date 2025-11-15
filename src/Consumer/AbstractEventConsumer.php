<?php
declare(strict_types=1);

namespace Elandlord\NatsPhp\Consumer;

use Elandlord\NatsPhp\Contract\Consumer\EventConsumerInterface;
use Elandlord\NatsPhp\Contract\Handler\EventHandlerInterface;
use Elandlord\NatsPhp\Messaging\EventEnvelope;
use Elandlord\NatsPhp\Nats\Model\NatsConnection;
use Exception;
use Throwable;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
abstract class AbstractEventConsumer implements EventConsumerInterface
{
    /**
     * @param EventHandlerInterface[] $handlers
     */
    public function __construct(
        protected readonly NatsConnection $connection,
        protected readonly array          $handlers,
        protected readonly string         $streamName,
        protected readonly string         $consumerName,
        protected readonly ?string        $subjectFilter = null,
        protected readonly ?int           $maxDeliver = null,
        protected readonly ?int           $ackWait = null,
    ) {
    }

    /**
     * @throws Exception
     */
    public function consume(): void
    {
        $client = $this->connection->getClient();
        $stream = $client->getApi()->getStream($this->streamName);

        $consumer = $stream->getConsumer($this->consumerName);

        $config = $consumer->getConfiguration();

        if ($this->subjectFilter !== null) {
            $config->setSubjectFilter($this->subjectFilter);
        }

        if ($this->ackWait !== null) {
            $config->setAckWait($this->ackWait);
        }

        if ($this->maxDeliver !== null) {
            $config->setMaxDeliver($this->maxDeliver);
        }

        $handlerMap = $this->buildHandlerMap($this->handlers);
        $queue = $consumer->getQueue();

        while ($message = $queue->next()) {
            try {
                $payload = $message->payload;

                if ($message->replyTo === null) {
                    continue;
                }

                /** @var EventEnvelope $envelope */
                $envelope = unserialize($payload->body, ['allowed_classes' => true]);

                if (!$envelope instanceof EventEnvelope) {
                    $message->ack();
                    continue;
                }

                $eventName = $envelope->eventName;
                $event = $envelope->body;

                if (!isset($handlerMap[$eventName])) {
                    $message->ack();
                    continue;
                }

                $handlerMap[$eventName]->handle($event);

                $message->ack();
            } catch (Throwable $e) {
                $message->nack(1.0);
            }
        }
    }

    /**
     * @param EventHandlerInterface[] $handlers
     * @return array<string, EventHandlerInterface>
     */
    private function buildHandlerMap(array $handlers): array
    {
        $map = [];
        foreach ($handlers as $handler) {
            $map[$handler->getHandledEventName()] = $handler;
        }
        return $map;
    }
}
