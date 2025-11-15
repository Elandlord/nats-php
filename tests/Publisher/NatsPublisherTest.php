<?php
declare(strict_types=1);

use Basis\Nats\Client;
use Elandlord\NatsPhp\Connection\NatsConnection;
use Elandlord\NatsPhp\Publisher\NatsPublisher;

it('publishes messages to NATS through the client', function () {
    // Arrange
    $mockClient = Mockery::mock(Client::class);
    $mockConnection = Mockery::mock(NatsConnection::class);

    $mockConnection
        ->shouldReceive('getClient')
        ->once()
        ->andReturn($mockClient);

    $mockClient
        ->shouldReceive('publish')
        ->once()
        ->with('test.subject', 'payload-data');

    // Act
    $publisher = new NatsPublisher($mockConnection);
    $publisher->publish('test.subject', 'payload-data');

    // Assert
});
