<?php
declare(strict_types=1);

namespace Elandlord\NatsPhp\Publisher;

use Elandlord\NatsPhp\Connection\NatsConnection;
use Elandlord\NatsPhp\Contract\Model\SubjectPublisherInterface;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
class NatsPublisher implements SubjectPublisherInterface
{
    public function __construct(
        protected readonly NatsConnection $connection
    ) {
    }

    public function publish(string $subject, string $payload): void
    {
        $client = $this->connection->getClient();
        $client->publish($subject, $payload);
    }
}
