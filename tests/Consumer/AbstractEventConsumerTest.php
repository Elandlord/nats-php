<?php
declare(strict_types=1);

use Basis\Nats\Api;
use Basis\Nats\Client;
use Basis\Nats\Consumer\Configuration;
use Basis\Nats\Consumer\Consumer;
use Basis\Nats\Stream\Stream;
use Elandlord\NatsPhp\Connection\NatsConnection;
use Elandlord\NatsPhp\Consumer\AbstractEventConsumer;

const STREAM_NAME = 'FLOW';
const CONSUMER_NAME = 'flow-consumer';
const SUBJECT_FILTER = 'flow.>';
const MAX_DELIVER = 3;
const ACK_WAIT_MS = 900_000;
const ACK_WAIT_NS = 900_000_000_000;
const STALE_ACK_WAIT_NS = 10_000_000_000;

function makeEventConsumer(Mockery\MockInterface $client): AbstractEventConsumer
{
    $connection = new NatsConnection($client);

    return new class ($connection, [], STREAM_NAME, CONSUMER_NAME, SUBJECT_FILTER, MAX_DELIVER, ACK_WAIT_MS) extends AbstractEventConsumer {
        public function exposeGetOrCreateConsumer(Stream $stream): Consumer
        {
            return $this->getOrCreateConsumer($stream);
        }
    };
}

function makeStreamWithConsumer(Mockery\MockInterface $client, array $existingConsumerNames): Mockery\MockInterface
{
    $consumer = new Consumer($client, new Configuration(STREAM_NAME, CONSUMER_NAME));

    $stream = Mockery::mock(Stream::class);
    $stream->shouldReceive('getConsumer')->with(CONSUMER_NAME)->andReturn($consumer);
    $stream->shouldReceive('getConsumerNames')->andReturn($existingConsumerNames);

    $api = Mockery::mock(Api::class);
    $api->shouldReceive('getStream')->with(STREAM_NAME)->andReturn($stream);
    $client->shouldReceive('getApi')->andReturn($api);

    return $stream;
}

function liveConsumerInfo(int $ackWait): object
{
    return (object) ['config' => (object) [
        'ack_wait' => $ackWait,
        'max_deliver' => MAX_DELIVER,
        'filter_subject' => SUBJECT_FILTER,
    ]];
}

it('updates an existing consumer when its live configuration differs', function () {
    // Arrange
    $client = Mockery::mock(Client::class);
    $stream = makeStreamWithConsumer($client, [CONSUMER_NAME]);

    $client->shouldReceive('api')
        ->with('CONSUMER.INFO.' . STREAM_NAME . '.' . CONSUMER_NAME)
        ->once()
        ->andReturn(liveConsumerInfo(STALE_ACK_WAIT_NS));

    $client->shouldReceive('api')
        ->withArgs(function (string $command, array $args): bool {
            return $command === 'CONSUMER.DURABLE.CREATE.' . STREAM_NAME . '.' . CONSUMER_NAME
                && $args['config']['ack_wait'] === ACK_WAIT_NS
                && $args['config']['max_deliver'] === MAX_DELIVER
                && $args['config']['filter_subject'] === SUBJECT_FILTER;
        })
        ->once()
        ->andReturn((object) []);

    // Act
    $consumer = makeEventConsumer($client)->exposeGetOrCreateConsumer($stream);

    // Assert
    expect($consumer->getConfiguration()->getAckWait())->toBe(ACK_WAIT_NS);
});

it('leaves an existing consumer alone when its live configuration matches', function () {
    // Arrange
    $client = Mockery::mock(Client::class);
    $stream = makeStreamWithConsumer($client, [CONSUMER_NAME]);

    $client->shouldReceive('api')
        ->with('CONSUMER.INFO.' . STREAM_NAME . '.' . CONSUMER_NAME)
        ->once()
        ->andReturn(liveConsumerInfo(ACK_WAIT_NS));

    $client->shouldNotReceive('api')
        ->with('CONSUMER.DURABLE.CREATE.' . STREAM_NAME . '.' . CONSUMER_NAME, Mockery::any());

    // Act
    $consumer = makeEventConsumer($client)->exposeGetOrCreateConsumer($stream);

    // Assert
    expect($consumer->getName())->toBe(CONSUMER_NAME);
});

it('creates the consumer when it does not exist yet', function () {
    // Arrange
    $client = Mockery::mock(Client::class);
    $stream = makeStreamWithConsumer($client, []);

    $client->shouldNotReceive('api')
        ->with('CONSUMER.INFO.' . STREAM_NAME . '.' . CONSUMER_NAME);

    $client->shouldReceive('api')
        ->withArgs(function (string $command, array $args): bool {
            return $command === 'CONSUMER.DURABLE.CREATE.' . STREAM_NAME . '.' . CONSUMER_NAME
                && $args['config']['ack_wait'] === ACK_WAIT_NS;
        })
        ->once()
        ->andReturn((object) []);

    // Act
    $consumer = makeEventConsumer($client)->exposeGetOrCreateConsumer($stream);

    // Assert
    expect($consumer->exists())->toBeTrue();
});
