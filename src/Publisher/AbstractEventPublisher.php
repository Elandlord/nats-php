<?php
declare(strict_types=1);

namespace Elandlord\NatsPhp\Publisher;

use Elandlord\NatsPhp\Contract\Message\EventMessageInterface;
use Elandlord\NatsPhp\Contract\Model\SubjectPublisherInterface;
use Elandlord\NatsPhp\Contract\Publisher\EventPublisherInterface;
use Elandlord\NatsPhp\Messaging\EventEnvelope;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
abstract readonly class AbstractEventPublisher implements EventPublisherInterface
{
    public function __construct(
        protected SubjectPublisherInterface $publisher,
    ) {
    }

    public function publish(EventMessageInterface $event): void
    {
        $eventName = $event->getEventName();

        $envelope = new EventEnvelope(
            eventName: $eventName,
            body: $event
        );

        $payload = serialize($envelope);
        $subject = $this->buildSubject($eventName);

        $this->publisher->publish($subject, $payload);
    }

    protected function buildSubject(string $eventName): string
    {
        return sprintf('%s.%s', $this->getSubjectPrefix(), $eventName);
    }

    abstract protected function getSubjectPrefix(): string;
}
