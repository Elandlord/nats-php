<?php
declare(strict_types=1);

namespace Elandlord\NatsPhp\Contract\Handler;

use Elandlord\NatsPhp\Contract\Message\EventMessageInterface;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
interface EventHandlerInterface
{
    public function getHandledEventName(): string;

    public function handle(EventMessageInterface $event): void;
}
