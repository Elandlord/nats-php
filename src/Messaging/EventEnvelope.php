<?php
declare(strict_types=1);

namespace Elandlord\NatsPhp\Messaging;

use Elandlord\NatsPhp\Contract\Message\EventMessageInterface;
use JsonSerializable;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
class EventEnvelope implements JsonSerializable
{
    public const EVENT_NAME_KEY = 'eventName';
    public const BODY_KEY = 'body';

    public function __construct(
        public string                $eventName,
        public EventMessageInterface $body
    )
    {
    }

    public function jsonSerialize(): array
    {
        return [
            self::EVENT_NAME_KEY => $this->eventName,
            self::BODY_KEY => $this->body,
        ];
    }
}
