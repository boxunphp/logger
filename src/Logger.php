<?php
namespace All\Logger;

use All\Instance\InstanceTrait;
use All\Request\Request;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;

/**
 * 日志类
 * Class Logger
 * @package All\Logger
 *
 * 根据PSR-3: Logger Interface
 *  https://www.php-fig.org/psr/psr-3/
 */
class Logger implements LoggerInterface
{
    use InstanceTrait;
    use LoggerTrait;

    const DEBUG       = 0x00000001;
    const INFO        = 0x00000010;
    const NOTICE      = 0x00000100;
    const WARNING     = 0x00001000;
    const ERROR       = 0x00010000;
    const CRITICAL    = 0x00100000;
    const ALERT       = 0x01000000;
    const EMERGENCY   = 0x10000000;

    /**
     * @var int 错误等级
     */
    protected static $level = LogLevel::INFO;
    protected static $levels = [
        LogLevel::DEBUG => self::DEBUG,
        LogLevel::INFO => self::INFO,
        LogLevel::NOTICE => self::NOTICE,
        LogLevel::WARNING => self::WARNING,
        LogLevel::ERROR => self::ERROR,
        LogLevel::CRITICAL => self::CRITICAL,
        LogLevel::ALERT => self::ALERT,
        LogLevel::EMERGENCY => self::EMERGENCY,
    ];

    const HANDLER_FILE = 'file';
    const HANDLER_STDOUT = 'stdout';

    /**
     * @var HandlerInterface
     */
    protected $handler;

    /**
     * 处理日志内空的句柄
     *
     * @param HandlerInterface $handler
     * @return void
     */
    public function setHandler(HandlerInterface $handler)
    {
        $this->handler = $handler;
    }

    /**
     * 设置日志等级
     *
     * @param string $level
     * @return void
     */
    public static function setLevel(string $level)
    {
        self::$level = $level;
    }

    public function log($level, $message, array $context = [])
    {
        if (!isset(self::$levels[$level])) {
            throw new InvalidArgumentException();
        }

        if (self::$levels[$level] < self::$levels[self::$level]) {
            return;
        }

        $request = Request::getInstance();
        $time = date('c');
        $reqId = $request->requestId();
        $host = $request->isCli() ? 'cli' : $request->serverHost();
        $serverIp = $request->serverIp();
        $clientIp = $request->clientIp();

        if (is_string($message)) {
            $message = str_replace(["\r", "\n"], ' ', $message);
        } else {
            $message = json_encode(
                $message,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR
            );
        }

        $log = [
            'time' => $time,
            'level' => $level,
            'host' => $host,
            'reqid' => $reqId,
            'server_ip' => $serverIp,
            'client_ip' => $clientIp,
            'message' => $message
        ];

        $this->handler->write($log);
    }
}
