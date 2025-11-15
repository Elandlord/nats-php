<?php

use Basis\Nats\Client;
use Basis\Nats\Configuration;
use Elandlord\NatsPhp\Connection\NatsConnection;

it('returns the client instance', function () {
    // Arrange
    $client = Mockery::mock(Client::class);

    // Act
    $conn = new NatsConnection($client);

    // Assert
    expect($conn->getClient())->toBe($client);
});

it('healthCheck returns true when ping succeeds', function () {
    // Arrange
    $client = Mockery::mock(Client::class);
    $client->shouldReceive('ping')
        ->once()
        ->andReturnTrue();

    // Act
    $conn = new NatsConnection($client);

    // Assert
    expect($conn->healthCheck())
        ->toBeTrue();
});

it('healthCheck returns false when ping throws', function () {
    // Arrange
    $client = Mockery::mock(Client::class);
    $client->shouldReceive('ping')->once()->andThrow(new RuntimeException('offline'));

    // Act
    $conn = new NatsConnection($client);

    // Assert
    expect($conn->healthCheck())->toBeFalse();
});

it('supports creating from configuration', function () {
    // Arrange
    $clientMock = Mockery::mock(Client::class);

    $clientFactory = function (Configuration $configuration) use ($clientMock): Client {
        return $clientMock;
    };

    // Act
    $connection = NatsConnection::fromConfiguration(null, $clientFactory);

    // Assert
    expect($connection->getClient())->toBe($clientMock);
});
