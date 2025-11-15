<?php
declare(strict_types=1);

namespace Elandlord\NatsPhp\Contract\Connection;

use Basis\Nats\Client;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
interface NatsConnectionInterface
{
    public function getClient(): Client;
}
