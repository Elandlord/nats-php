<?php
declare(strict_types=1);

namespace Elandlord\NatsPhp\Contract\Handler;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
interface EventHandlerInterface
{
    public function getHandledEventName(): string;

    public function handle(array $event): void;
}
