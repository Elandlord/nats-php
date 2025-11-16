<?php
declare(strict_types=1);

namespace Elandlord\NatsPhp\Connection;

use Basis\Nats\Client;
use Basis\Nats\Configuration;
use Elandlord\NatsPhp\Contract\Connection\NatsConnectionInterface;
use Throwable;

/**
 * @copyright 2025
 * @license MIT
 */
class NatsConnection implements NatsConnectionInterface
{
    protected Client $client;

    public const DEFAULT_CONFIGURATION = [
        'host' => 'localhost',
        'port' => 4222,
        'user' => null,
        'pass' => null,
        'reconnect' => true,
        'pedantic' => false,
    ];

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public static function fromConfiguration(
        ?Configuration $configuration = null,
        ?callable $clientFactory = null
    ): self {
        $configuration = $configuration ?? self::createDefaultConfiguration();

        $clientFactory = $clientFactory ?? static function (Configuration $configuration): Client {
            return new Client($configuration);
        };

        $client = $clientFactory($configuration);

        return new self($client);
    }

    public static function createDefaultConfiguration(): Configuration
    {
        $configuration = new Configuration(self::DEFAULT_CONFIGURATION);

        // Delay must be called explicitly
        $configuration->setDelay(0.01);

        return $configuration;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function healthCheck(): bool
    {
        try {
            $this->client->ping();
            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
