<?php
declare(strict_types=1);

namespace Elandlord\NatsPhp\Consumer;

use Basis\Nats\Consumer\Consumer;
use Basis\Nats\Message\Msg;
use Basis\Nats\Stream\Stream;
use CloudEvents\Exceptions\InvalidPayloadSyntaxException;
use CloudEvents\Exceptions\MissingAttributeException;
use CloudEvents\Exceptions\UnsupportedSpecVersionException;
use CloudEvents\Serializers\JsonDeserializer;
use CloudEvents\V1\CloudEventInterface;
use Elandlord\NatsPhp\Connection\NatsConnection;
use Elandlord\NatsPhp\Contract\Consumer\EventConsumerInterface;
use Elandlord\NatsPhp\Contract\Handler\EventHandlerInterface;
use Elandlord\NatsPhp\Exception\InvalidCloudEventException;
use Exception;
use JsonException;
use Throwable;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
abstract class AbstractEventConsumer implements EventConsumerInterface
{
    public const DEFAULT_MAX_DELIVER = 3;
    public const DEFAULT_ACK_WAIT_MS = 10_000;
    protected const NANOSECONDS_PER_MILLISECOND = 1_000_000;

    /** @var array<string, EventHandlerInterface> */
    protected array $handlerMap;

    /**
     * @param EventHandlerInterface[] $handlers
     */
    public function __construct(
        protected readonly NatsConnection $connection,
        protected readonly array          $handlers,
        protected readonly string         $streamName,
        protected readonly string         $consumerName,
        protected readonly ?string        $subjectFilter = null,
        protected readonly int            $maxDeliver = self::DEFAULT_MAX_DELIVER,
        protected readonly int            $ackWait = self::DEFAULT_ACK_WAIT_MS,
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

        $consumer = $this->getOrCreateConsumer($stream);
        $queue = $consumer->getQueue();

        while ($message = $queue->next()) {
            $this->processMessage($message);
        }
    }

    protected function getOrCreateConsumer(Stream $stream): Consumer
    {
        $consumer = $stream->getConsumer($this->consumerName);
        $config = $consumer->getConfiguration();

        if ($this->subjectFilter !== null) {
            $config->setSubjectFilter($this->subjectFilter);
        }

        $config->setAckWait($this->ackWait * self::NANOSECONDS_PER_MILLISECOND);
        $config->setMaxDeliver($this->maxDeliver);

        if ($consumer->exists() && $this->hasConfigurationDrift($consumer)) {
            $this->updateConsumer($consumer);

            return $consumer;
        }

        return $consumer->create();
    }

    protected function hasConfigurationDrift(Consumer $consumer): bool
    {
        $live = $consumer->info()->config;
        $desired = $consumer->getConfiguration()->toArray()['config'];

        return ($live->ack_wait ?? null) !== ($desired['ack_wait'] ?? null)
            || ($live->max_deliver ?? null) !== ($desired['max_deliver'] ?? null)
            || ($live->filter_subject ?? null) !== ($desired['filter_subject'] ?? null);
    }

    protected function updateConsumer(Consumer $consumer): void
    {
        $consumer->client->api(
            sprintf('CONSUMER.DURABLE.CREATE.%s.%s', $consumer->getStream(), $consumer->getName()),
            $consumer->getConfiguration()->toArray(),
        );
    }

    protected function processMessage(Msg $message): void
    {
        try {
            if (!$this->shouldProcess($message)) {
                return;
            }

            $cloudEvent = $this->extractCloudEvent($message);
            $handler = $this->resolveHandler($cloudEvent);

            if ($handler === null) {
                $message->ack();
                return;
            }

            $handler->handle($cloudEvent);
            $message->ack();
        } catch (Throwable $exception) {
            $message->nack(1.0);
            $this->onProcessingError($exception, $message);
        }
    }

    /**
     * @throws UnsupportedSpecVersionException
     * @throws InvalidPayloadSyntaxException
     * @throws MissingAttributeException
     */
    protected function extractCloudEvent(Msg $message): CloudEventInterface
    {
        $raw = $message->payload->body;

        $event = JsonDeserializer::create()->deserializeStructured($raw);

        if (!$event instanceof CloudEventInterface) {
            throw new InvalidCloudEventException('Unsupported CloudEvents spec version.');
        }

        return $event;
    }

    protected function shouldProcess(Msg $message): bool
    {
        return $message->replyTo !== null;
    }

    protected function onProcessingError(Throwable $exception, Msg $message): void
    {
        // Possible to override by subclasses
    }

    protected function resolveHandler(CloudEventInterface $event): ?EventHandlerInterface
    {
        return $this->handlerMap[$event->getType()] ?? null;
    }

    /**
     * @param EventHandlerInterface[] $handlers
     * @return array<string, EventHandlerInterface>
     */
    protected function buildHandlerMap(array $handlers): array
    {
        $map = [];
        foreach ($handlers as $handler) {
            $map[$handler->getHandledEventType()] = $handler;
        }
        return $map;
    }
}
