<?php
namespace Tests\Logger;

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
        Logger::setSavePath('/tmp');
        Logger::setLevel(LogLevel::DEBUG);
        Logger::setSaveHandler(Logger::HANDLER_FILE);

        $request = Request::getInstance();
        $this->time = date('c');
        $this->host = 'cli';
        $this->reqId = $request->requestId();
        $this->serverIp = $request->serverIp();
        $this->clientIp = $request->clientIp();
    }

    public function testFile()
    {
        $this->time = date('c');
        $logTmpl = [
            $this->time,
            $this->host,
            $this->reqId,
            $this->serverIp,
            $this->clientIp,
            ''
        ];
        $index = 5;

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

        $logger = Logger::getInstance();

        foreach ($files as $file) {
            foreach ($list as $message) {
                $filename = '/tmp/' . $file . '.log';
                @unlink($filename);
                $logger->{$file}($message);
                $log = $logTmpl;
                $log[$index] = $this->getMessage($message);
                $content = implode(' ', $log) . "\n";
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
