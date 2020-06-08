<?php
namespace All\Logger;

use All\Instance\InstanceTrait;
use All\Request\Request;
use Psr\Log\InvalidArgumentException;
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
class Logger
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

    const E_ALL =   self::DEBUG |
                    self::INFO |
                    self::NOTICE |
                    self::WARNING |
                    self::ERROR |
                    self::CRITICAL |
                    self::ALERT |
                    self::EMERGENCY;

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
     * 日志保存目录
     * @var string
     */
    protected static $savePath = '';
    /**
     * 日志保存类型
     * @var string
     */
    protected static $saveHandler = self::HANDLER_FILE;

    public static function setSavePath($savePath)
    {
        self::$savePath = $savePath;
    }

    public static function setSaveHandler($saveHandler)
    {
        self::$saveHandler = $saveHandler;
    }

    public static function setLevel($level)
    {
        self::$level = $level;
    }

    public function log($level, $message, array $context = [])
    {
        if (!isset(self::$levels[$level])) {
            throw new InvalidArgumentException();
        }

        if (self::$levels[$level] & self::E_ALL < self::$level) {
            return true;
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

        switch (self::$saveHandler) {
            case self::HANDLER_STDOUT:
                $log = [
                    'time' => $time,
                    'level' => $level,
                    'host' => $host,
                    'reqid' => $reqId,
                    'server_ip' => $serverIp,
                    'client_ip' => $clientIp,
                    'message' => $message
                ];

                $content = json_encode(
                    $log,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR
                ) . "\n";
                if ('php://stdout' == self::$savePath) {
                    $fp = defined('STDOUT') ? STDOUT : fopen('php://stdout', 'wb');
                    $result = $this->fwrite($fp, $content) !== false;
                } else {
                    $fp = fopen(self::$savePath, 'wb');
                    $result = $this->fwrite($fp, $content) !== false;
                    fclose($fp);
                }
                break;
            default:
                $log = [
                    $time,
                    $host,
                    $reqId,
                    $serverIp,
                    $clientIp,
                    $message
                ];
                $content = implode(' ', $log) . "\n";
                $dir = self::$savePath;
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                $filename = $dir . '/' . $level . '.log';
                $isFileExist = is_file($filename);
                $result = $this->errorLog($content, 3, $filename);
                if ($result && !$isFileExist) {
                    chmod($filename, 0777);
                }
                break;
        }

        return $result;
    }

    private function errorLog($message, $type, $file)
    {
        $arr = [];
        if (strlen($message) > 1024) {
            $arr = str_split($message, 1024);
        } else {
            $arr[] = $message;
        }
        foreach ($arr as $item) {
            if (!error_log($item, $type, $file)) {
                return false;
            }
        }
        return true;
    }

    private function fwrite($fp, $message)
    {
        $arr = [];
        $needLock = false;
        if (strlen($message) > 1024) {
            $needLock = true;
            $arr = str_split($message, 1024);
        } else {
            $arr[] = $message;
        }
        $bytes = 0;
        if ($needLock) {
            flock($fp, LOCK_EX);
        }
        foreach ($arr as $item) {
            $result = fwrite($fp, $item);
            if ($result === false) {
                if ($needLock) {
                    flock($fp, LOCK_UN);
                }
                return false;
            } else {
                $bytes += $result;
            }
        }
        if ($needLock) {
            flock($fp, LOCK_UN);
        }
        return $bytes;
    }
}
