<?php
declare(strict_types=1);

namespace Elandlord\NatsPhp\Contract\Model;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
interface SubjectPublisherInterface
{
    public function publish(string $subject, string $payload): void;
}
