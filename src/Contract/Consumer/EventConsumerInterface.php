<?php
declare(strict_types=1);

namespace Elandlord\NatsPhp\Contract\Consumer;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
interface EventConsumerInterface
{
    public function consume(): void;
}
