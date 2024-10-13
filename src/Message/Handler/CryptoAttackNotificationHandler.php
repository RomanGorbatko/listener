<?php

namespace App\Message\Handler;

use App\Enum\ProcessorTypeEnum;
use App\Event\TelegramLogEvent;
use App\Message\CryptoAttackNotification;
use App\Processor\Exception\FailedExtractElementException;
use App\Processor\Exception\UnsupportedTickerException;
use App\Processor\ProcessorFactory;
use phpseclib3\Math\BigInteger\Engines\PHP;
use Piscibus\PhpHashtag\Extractor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CryptoAttackNotificationHandler
{
    public function __construct(
        private ProcessorFactory $processorFactory,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(CryptoAttackNotification $message): void
    {
        $processorType = $this->resolveProcessorType($message->getContent());
        if (! $processorType instanceof ProcessorTypeEnum) {
            return;
        }

        try {
            $this->processorFactory
                ->getByProcessorType($processorType)
                ->processNotification(
                    $message->getContent(),
                    $message->getDatetime()
                );
        }  catch (UnsupportedTickerException $exception) {
            $logMessage = '⚡️<b>Unsupported ticker</b>' . PHP_EOL;
            $logMessage .= 'Ticker: <i>#' . $exception->getTicker() . '</i>' . PHP_EOL;
        }  catch (FailedExtractElementException $exception) {
            $logMessage = '⚡️<b>Failed extract element</b>' . PHP_EOL;
            $logMessage .= 'Type: <i>' . $exception->getType() . '</i>' . PHP_EOL;
        } catch (\Throwable $throwable) {
            $logMessage = '⚡️<b>Exception occurs NotificationHandler</b>' . PHP_EOL;
            $logMessage .= 'Class: <i>' . get_class($throwable) . '</i>' . PHP_EOL;
            $logMessage .= 'Message: <i>' . $throwable->getMessage() . '</i>';
        } finally {
            if (isset($logMessage)) {
                $this->eventDispatcher->dispatch(new TelegramLogEvent($logMessage));
            }
        }
    }

    private function resolveProcessorType(string $message): ?ProcessorTypeEnum
    {
        $hashtags = Extractor::extract($message);
        foreach ($hashtags as $hashtag) {
            $hashtag = ltrim($hashtag, '#');

            $processorType = ProcessorTypeEnum::tryFrom($hashtag);
            if ($processorType instanceof ProcessorTypeEnum) {
                return $processorType;
            }
        }

        return null;
    }
}
