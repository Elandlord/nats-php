<?php
declare(strict_types=1);

namespace Elandlord\NatsPhp\Publisher;

use Basis\Nats\Stream\Stream;
use Elandlord\NatsPhp\Connection\NatsConnection;
use Elandlord\NatsPhp\Contract\Model\SubjectPublisherInterface;
use RuntimeException;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
class JetStreamPublisher implements SubjectPublisherInterface
{
    protected Stream $stream;

    public function __construct(
        NatsConnection $connection,
        string         $streamName
    )
    {
        $client = $connection->getClient();

        $this->stream = $client->getApi()->getStream($streamName);

        if (!$this->stream->exists()) {
            throw new RuntimeException(sprintf(
                'JetStream stream "%s" does not exist',
                $streamName
            ));
        }
    }

    public function publish(string $subject, string $payload): void
    {
        $this->stream->put($subject, $payload);
    }
}
