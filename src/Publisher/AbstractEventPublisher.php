<?php
declare(strict_types=1);

namespace Elandlord\NatsPhp\Publisher;

use CloudEvents\Serializers\JsonSerializer;
use CloudEvents\V1\CloudEventInterface;
use Elandlord\NatsPhp\Contract\Model\SubjectPublisherInterface;
use Elandlord\NatsPhp\Contract\Publisher\EventPublisherInterface;

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

    public function publish(CloudEventInterface $event): void
    {
        $payload = JsonSerializer::create()->serializeStructured($event);

        $subject = $this->buildSubject($event->getType());
        $this->publisher->publish($subject, $payload);
    }

    protected function buildSubject(string $eventType): string
    {
        $prefix = $this->getSubjectPrefix();
        return $prefix !== '' ? sprintf('%s.%s', $prefix, $eventType) : $eventType;
    }

    abstract protected function getSubjectPrefix(): string;
}
