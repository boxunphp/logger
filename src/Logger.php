<?php
namespace All\Logger;

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
    protected $level = LogLevel::INFO;
    const LEVEL_MAPPER = [
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
     * @param string|null $level
     * @param HandlerInterface|null $handler
     */
    public function __construct(?string $level, ?HandlerInterface $handler)
    {
        if ($level && array_key_exists($level, self::LEVEL_MAPPER)) {
            $this->level = $level;
        }

        if ($handler) {
            $this->handler = $handler;
        }
    }

    /**
     * 处理日志内空的句柄
     *
     * @param HandlerInterface $handler
     * @return static
     */
    public function setHandler(HandlerInterface $handler)
    {
        $this->handler = $handler;
        return $this;
    }

    /**
     * 设置日志等级
     *
     * @param string $level
     * @return static
     */
    public function setLevel(string $level)
    {
        $this->level = $level;
        return $this;
    }

    public function log($level, $message, array $context = [])
    {
        if (!isset(self::LEVEL_MAPPER[$level])) {
            throw new InvalidArgumentException();
        }

        if (self::LEVEL_MAPPER[$level] < self::LEVEL_MAPPER[$this->level]) {
            return;
        }

        $request = Request::getInstance();
        $time = date('c');
        $reqId = $request->getRequestId();
        $host = 'cli' === PHP_SAPI ? 'cli' : $request->getServerHost();
        $serverIp = $request->getServerIp();
        $clientIp = $request->getClientIp();

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
