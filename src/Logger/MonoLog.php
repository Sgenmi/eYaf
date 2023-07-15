<?php
declare(strict_types=1);

namespace Sgenmi\eYaf\Logger;

use DateTimeZone;
use RuntimeException;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;
use Sgenmi\eYaf\Contract\StdoutLoggerInterface;

/**
 * 日志类
 * Author: sgenmi
 * Date: 2023/3/28 3:43 PM
 * Email: 150560159@qq.com
 */
class MonoLog implements StdoutLoggerInterface
{

    public function emergency($message, array $context = [], string $name = "local", string $fileName = '')
    {
        $this->log(LogLevel::EMERGENCY, $message, $context, $name, $fileName);
    }

    public function alert($message, array $context = [], string $name = "local", string $fileName = '')
    {
        $this->log(LogLevel::ALERT, $message, $context, $name, $fileName);
    }

    public function critical($message, array $context = [], string $name = "local", string $fileName = '')
    {
        $this->log(LogLevel::CRITICAL, $message, $context, $name, $fileName);
    }

    public function error($message, array $context = [], string $name = "local", string $fileName = '')
    {
        $this->log(LogLevel::ERROR, $message, $context, $name, $fileName);
    }

    public function warning($message, array $context = [], string $name = "local", string $fileName = '')
    {
        $this->log(LogLevel::WARNING, $message, $context, $name, $fileName);
    }

    public function notice($message, array $context = [], string $name = "local", string $fileName = '')
    {
        $this->log(LogLevel::NOTICE, $message, $context, $name, $fileName);
    }

    public function info($message, array $context = [], string $name = "local", string $fileName = '')
    {
        $this->log(LogLevel::INFO, $message, $context, $name, $fileName);
    }

    public function debug($message, array $context = [], string $name = "local", string $fileName = '')
    {
        $this->log(LogLevel::DEBUG, $message, $context, $name, $fileName);
    }

    public function log($level, $message, array $context = array(), string $name = "local", string $fileName = '')
    {
        $log = $this->_getLogger($name, $fileName);
        $log->log($level, self::getContent($message), $context);
    }

    /**
     * @param $content
     * @return string
     * @author Sgenmi
     */
    private static function getContent($content): string
    {
        if (is_array($content)) {
            return json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return (string)$content;
    }


    /**
     * @param array $config
     * @return Logger
     * @author Sgenmi
     */
    protected function getLoggerByLocal(array $config = []):Logger {
        $_fileName = "runtime.log";
        if (!empty($config['fileName'])) {
            $fileName = $config['fileName'];
            $_fileName = str_contains($fileName, ".log") ? $fileName : $fileName . ".log";
        }
        $config['filePath'] = LOG_PATH . "/" . $_fileName;
        $handle = new StreamHandler($config['filePath']);
        $dateFormat = "Y-m-d H:i:s";
        $output = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"; # 日志内容格式
        $line_formatter = new LineFormatter($output, $dateFormat);
        $handle->setFormatter($line_formatter);
        $log = new Logger('local');
        $log->pushHandler($handle);
        $log->setTimezone(new DateTimeZone('Asia/Shanghai'));
        return $log;
    }

    /**
     * @param string $name
     * @param string $fileName
     * @return Logger
     * @author Sgenmi
     */
    private  function _getLogger(string $name, string $fileName=''): Logger
    {
        if(empty($name)){
            $name = 'local';
        }
        $config=[
            'name'=>$name,
            'fileName'=>$fileName
        ];
        $getLoggerFun = 'getLoggerBy'.ucfirst($name);
        if(!method_exists($this,$getLoggerFun)){
            throw new InvalidArgumentException("not fund ".static::class.'::'.$getLoggerFun);
        }
        $logger = $this->$getLoggerFun($config);
        if(!$logger instanceof Logger){
            throw new RuntimeException(sprintf("Driver %s is not invalid",$name));
        }
        return $logger;
    }
}
