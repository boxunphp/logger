<?php
namespace All\Logger\Handler;

use All\Logger\HandlerInterface;

/**
 * 输出标准输出
 */
class StreamHandler implements HandlerInterface
{
    /**
     * 日志保存的文件
     * @var string
     */
    protected $filename = '';

    public function write(array $message): void
    {
        $content = json_encode(
            $message,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR
        ) . "\n";

        if ('php://stdout' === $this->filename && defined('STDOUT')) {
            $this->fwrite(STDOUT, $content);
        } elseif ('php://stderr' === $this->filename && defined('STDERR')) {
            $this->fwrite(STDERR, $content);
        } else {
            $fp = fopen($this->filename, 'wb');
            $this->fwrite($fp, $content);
            fclose($fp);
        }
    }

    /**
     * 设置日志输出文件
     *
     * @param string $filename
     * @return void
     */
    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    /**
     * 写入日志文件
     *
     * @param resource $fp
     * @param string $message
     * @return int
     */
    private function fwrite($fp, $message)
    {
        return fwrite($fp, $message);
    }
}
