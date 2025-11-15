<?php
declare(strict_types=1);

namespace Elandlord\NatsPhp\Contract\Message;

/**
 * Implement this on objects (DTO) that are sent as NATS events.
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
interface EventMessageInterface
{
    public function getEventName(): string;
}
