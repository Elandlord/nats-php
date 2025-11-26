<?php
declare(strict_types=1);

namespace Elandlord\NatsPhp\Contract\Publisher;

use CloudEvents\V1\CloudEventInterface;

interface EventPublisherInterface
{
    public function publish(CloudEventInterface $event): void;
}