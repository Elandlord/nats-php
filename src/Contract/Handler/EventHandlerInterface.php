<?php
declare(strict_types=1);

namespace Elandlord\NatsPhp\Contract\Handler;

use CloudEvents\V1\CloudEventInterface;

interface EventHandlerInterface
{
    public function getHandledEventType(): string;

    public function handle(CloudEventInterface $event): void;
}
