<?php
namespace Tests\Logger;

use All\Logger\Handler\FileHandler;
use All\Logger\Handler\StdoutHandler;
use All\Logger\Logger;
use All\Request\Request;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

class LoggerTest extends TestCase
{
    protected $time;
    protected $host;
    protected $reqId;
    protected $serverIp;
    protected $clientIp;

    protected function setUp(): void
    {
        Logger::setLevel(LogLevel::DEBUG);

        $request = Request::getInstance();
        $this->time = date('c');
        $this->host = 'cli';
        $this->reqId = $request->requestId();
        $this->serverIp = $request->serverIp();
        $this->clientIp = $request->clientIp();
    }

    public function testFileHandler()
    {
        $handler = new FileHandler();
        $handler->setSavePath('/tmp');

        $Logger = Logger::getInstance();
        $Logger->setHandler($handler);

        $logTmpl = [
            'time' => $this->time,
            'level' => LogLevel::DEBUG,
            'host' => $this->host,
            'reqid' => $this->reqId,
            'server_ip' => $this->serverIp,
            'client_ip' => $this->clientIp,
            'message' => '',
        ];

        $list = [
            'abc',
            ['abc' => 'ABC', 'efg' => 'EFG'],
        ];

        $files = [
            LogLevel::DEBUG,
            LogLevel::INFO,
            LogLevel::NOTICE,
            LogLevel::WARNING,
            LogLevel::ERROR,
            LogLevel::CRITICAL,
            LogLevel::ALERT,
            LogLevel::EMERGENCY,
        ];

        foreach ($files as $file) {
            foreach ($list as $message) {
                $filename = '/tmp/' . $file . '.log';
                @unlink($filename);
                $Logger->{$file}($message);
                $log = $logTmpl;
                $log['level'] = $file;
                $log['message'] = $this->getMessage($message);
                $content = implode(' ', array_values($log)) . "\n";
                $this->assertEquals($content, file_get_contents($filename));
            }
        }
    }

    public function testStdoutHandler()
    {
        $filename = '/tmp/logger.log';
        $handler = new StdoutHandler();
        $handler->setFilename($filename);

        $Logger = Logger::getInstance();
        $Logger->setHandler($handler);

        $logTmpl = [
            'time' => $this->time,
            'level' => LogLevel::DEBUG,
            'host' => $this->host,
            'reqid' => $this->reqId,
            'server_ip' => $this->serverIp,
            'client_ip' => $this->clientIp,
            'message' => '',
        ];

        $list = [
            'abc',
            ['abc' => 'ABC', 'efg' => 'EFG'],
        ];

        $files = [
            LogLevel::DEBUG,
            LogLevel::INFO,
            LogLevel::NOTICE,
            LogLevel::WARNING,
            LogLevel::ERROR,
            LogLevel::CRITICAL,
            LogLevel::ALERT,
            LogLevel::EMERGENCY,
        ];

        @unlink($filename);
        foreach ($files as $file) {
            foreach ($list as $message) {
                $Logger->{$file}($message);
                $log = $logTmpl;
                $log['level'] = $file;
                $log['message'] = $this->getMessage($message);
                $content = json_encode(
                    $log,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR
                ) . "\n";
                $this->assertEquals($content, file_get_contents($filename));
            }
        }
    }

    public function testLogLevel()
    {
        $handler = new FileHandler();
        $handler->setSavePath('/tmp');

        $Logger = Logger::getInstance();
        $Logger->setHandler($handler);

        $logTmpl = [
            'time' => $this->time,
            'level' => LogLevel::DEBUG,
            'host' => $this->host,
            'reqid' => $this->reqId,
            'server_ip' => $this->serverIp,
            'client_ip' => $this->clientIp,
            'message' => '',
        ];

        $message = 'abc';
        $logTmpl['message'] = $this->getMessage($message);

        $files = [
            LogLevel::DEBUG,
            LogLevel::INFO,
            LogLevel::NOTICE,
            LogLevel::WARNING,
            LogLevel::ERROR,
            LogLevel::CRITICAL,
            LogLevel::ALERT,
            LogLevel::EMERGENCY,
        ];

        Logger::setLevel(LogLevel::WARNING);

        foreach ($files as $file) {
            $filename = '/tmp/' . $file . '.log';
            @unlink($filename);

            $Logger->{$file}($message);

            if (in_array($file, [LogLevel::DEBUG, LogLevel::INFO, LogLevel::NOTICE])) {
                $this->assertFalse(file_exists($filename));
            } else {
                $this->assertTrue(file_exists($filename));
                $log = $logTmpl;
                $log['level'] = $file;
                $content = implode(' ', array_values($log)) . "\n";
                $this->assertEquals($content, file_get_contents($filename));
            }
        }
    }

    protected function getMessage($data)
    {
        if (is_string($data)) {
            $message = str_replace(["\r", "\n"], ' ', $data);
        } else {
            $message = json_encode(
                $data,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR
            );
        }
        return $message;
    }
}
