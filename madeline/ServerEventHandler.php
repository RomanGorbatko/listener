<?php declare(strict_types=1);

use App\Message\CryptoAttackNotification;
use danog\MadelineProto\EventHandler\Attributes\Handler;
use danog\MadelineProto\EventHandler\Filter\FilterFromSender;
use danog\MadelineProto\EventHandler\Message\PrivateMessage;
use danog\MadelineProto\Settings;
use danog\MadelineProto\SimpleEventHandler;
use danog\MadelineProto\Logger;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransport;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Messenger\Stamp\SentStamp;
use Symfony\Component\Messenger\Stamp\StampInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;

if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
}

$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__.'/../.env');

$appId = $_ENV['TELEGRAM_API_ID'] ?? null;
$appHash = $_ENV['TELEGRAM_API_HASH'] ?? null;
$chatId = $_ENV['TELEGRAM_CHAT_ID'] ?? null;
$messengerDsn = $_ENV['MESSENGER_TRANSPORT_DSN'] ?? null;

$session = __DIR__ . '/session/' . $appId;
if (!mkdir($session, recursive: true) && !is_dir($session)) {
    throw new RuntimeException(sprintf('Directory "%s" was not created', $session));
}

function buildSettings(int $appId, string $appHash): Settings
{
    $settings = new Settings();
    $settings
        ->getAppInfo()
        ->setApiId($appId)
        ->setApiHash($appHash)
    ;
    $settings
        ->getLogger()
        ->setType(Logger::LOGGER_ECHO)
        ->setExtra(static function () {
            // @todo implement logging
            // ->setType(Logger::LOGGER_CALLABLE)
        })
    ;

    return $settings;
}

class ServerEventHandler extends SimpleEventHandler
{
    private const EXCHANGE_NAME = 'messages';

    private AMQPStreamConnection|null $connection = null;

    #[Handler]
    public function handleMessage(PrivateMessage $message): void
    {
        global $chatId;

        $filter = new FilterFromSender($chatId);
        $filter->initialize($this);

        if (!$filter->apply($message)) {
            return;
        }

        $datetime = (new DateTimeImmutable())
            ->setTimestamp($message->date);

        $notification = Envelope::wrap(
            new CryptoAttackNotification($message->message, $datetime),
            $this->getStamps(),
        );

        $this->send($notification);
    }

    private function send(Envelope $envelope): void
    {
        $encodedMessage = (new PhpSerializer())->encode($envelope);

        try {
            $connection = $this->getAMQPConnection();
            $channel = $connection->channel();

            $channel->queue_declare(self::EXCHANGE_NAME, durable: true, auto_delete: false);
            $channel->exchange_declare(self::EXCHANGE_NAME, AMQPExchangeType::FANOUT, durable: true, auto_delete: false);

            $channel->queue_bind(self::EXCHANGE_NAME, self::EXCHANGE_NAME);

            $message = new AMQPMessage($encodedMessage['body']);
            $channel->basic_publish($message, self::EXCHANGE_NAME);

            $channel->close();
        } catch (Throwable) {
            // @todo log exceptions
        }
    }

    /**
     * @return StampInterface[]
     */
    private function getStamps(): array
    {
        return [
            new BusNameStamp('messenger.bus.default'),
            new SentStamp(AmqpTransport::class, 'async')
        ];
    }

    /**
     * @throws Exception
     */
    private function getAMQPConnection(): AMQPStreamConnection
    {
        if (! $this->connection instanceof AMQPStreamConnection) {
            global $messengerDsn;

            $connectionParams = parse_url($messengerDsn);
            $this->connection = new AMQPStreamConnection(
                $connectionParams['host'], $connectionParams['port'],
                $connectionParams['user'], $connectionParams['pass'],
                $connectionParams['path']
            );
        }

        return $this->connection;
    }
}

ServerEventHandler::startAndLoop($session, buildSettings((int) $appId, $appHash));
