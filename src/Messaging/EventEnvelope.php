<?php
declare(strict_types=1);

namespace Elandlord\NatsPhp\Messaging;

use Elandlord\NatsPhp\Contract\Message\EventMessageInterface;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
class EventEnvelope
{
    public function __construct(
        public string $eventName,
        public EventMessageInterface $body
    ) {
    }
}
