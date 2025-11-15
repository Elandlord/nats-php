<?php
declare(strict_types=1);

use Basis\Nats\Api;
use Basis\Nats\Client;
use Basis\Nats\Stream\Stream;
use Elandlord\NatsPhp\Connection\NatsConnection;
use Elandlord\NatsPhp\Publisher\JetStreamPublisher;

it('constructs successfully when JetStream stream exists', function () {
    // Arrange
    $streamName = 'FLOW';

    $connection = Mockery::mock(NatsConnection::class);
    $client = Mockery::mock(Client::class);
    $api = Mockery::mock(Api::class);
    $stream = Mockery::mock(Stream::class);

    $connection->shouldReceive('getClient')
        ->once()
        ->andReturn($client);

    $client->shouldReceive('getApi')
        ->once()
        ->andReturn($api);

    $api->shouldReceive('getStream')
        ->with($streamName)
        ->once()
        ->andReturn($stream);

    $stream->shouldReceive('exists')->once()->andReturn(true);

    // Act
    $publisher = new JetStreamPublisher($connection, $streamName);

    // Assert
    expect($publisher)->toBeInstanceOf(JetStreamPublisher::class);
});

it('throws when JetStream stream does NOT exist', function () {
    $streamName = 'MISSING_STREAM';

    $connection = Mockery::mock(NatsConnection::class);
    $client = Mockery::mock(Client::class);
    $api = Mockery::mock(Api::class);
    $stream = Mockery::mock(Stream::class);

    $connection->shouldReceive('getClient')
        ->once()
        ->andReturn($client);

    $client->shouldReceive('getApi')
        ->once()
        ->andReturn($api);

    $api->shouldReceive('getStream')
        ->with($streamName)
        ->once()
        ->andReturn($stream);

    $stream->shouldReceive('exists')->once()->andReturn(false);

    new JetStreamPublisher($connection, $streamName);
})->throws(RuntimeException::class, 'JetStream stream "MISSING_STREAM" does not exist');

it('publishes using stream->put()', function () {
    // Arrange
    $streamName = 'FLOW';
    $subject = 'flow.order.created';
    $payload = 'test-payload';

    $connection = Mockery::mock(NatsConnection::class);
    $client = Mockery::mock(Client::class);
    $api = Mockery::mock(Api::class);
    $stream = Mockery::mock(Stream::class);

    $connection->shouldReceive('getClient')
        ->once()
        ->andReturn($client);

    $client
        ->shouldReceive('getApi')
        ->once()
        ->andReturn($api);

    $api->shouldReceive('getStream')
        ->with($streamName)
        ->once()
        ->andReturn($stream);

    $stream->shouldReceive('exists')->once()->andReturn(true);

    $stream->shouldReceive('put')
        ->with($subject, $payload)
        ->once();

    // Act
    $publisher = new JetStreamPublisher($connection, $streamName);
    $publisher->publish($subject, $payload);

    // Assert
    expect(true)->toBeTrue();
});
