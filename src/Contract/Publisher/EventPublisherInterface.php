<?php
declare(strict_types=1);

namespace Elandlord\NatsPhp\Contract\Publisher;

use Elandlord\NatsPhp\Contract\Message\EventMessageInterface;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
interface EventPublisherInterface
{
    public function publish(EventMessageInterface $event): void;
}
