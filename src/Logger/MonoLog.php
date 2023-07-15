<?php
declare(strict_types=1);

namespace Sgenmi\eYaf\Logger;

use DateTimeZone;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
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
        $log = $this->getLogger($name, $fileName);
        $log->log($level, self::getContent($message), $context);
    }


    private static function getContent($content): string
    {
        if (is_array($content)) {
            return json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return (string)$content;
    }

    private  function getLogger(string $name = 'local', string $fileName = ''): Logger
    {
        $config = [];
        if ($name == 'local') {
            $_fileName = "runtime.log";
            if ($fileName) {
                $_fileName = str_contains($fileName, ".log") ? $fileName : $fileName . ".log";
            }
            $config['filePath'] = LOG_PATH . "/" . $_fileName;
        }
        return self::_getLogger($name, $config);
    }

    private static function _getLogger(string $name, array $config = []): Logger
    {
        switch ($name) {
            case "" || $name == 'local':
                $handle = new StreamHandler($config['filePath']);
                $dateFormat = "Y-m-d H:i:s";
                $output = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"; # 日志内容格式
                $line_formatter = new LineFormatter($output, $dateFormat);
                $handle->setFormatter($line_formatter);
                $log = new Logger('local');
                $log->pushHandler($handle);
                break;
//            case "db":
//                $hd = new StreamHandler($config['filePath']);
//                break;
//            case 'email':
//                $hd = new NativeMailerHandler($config['to'] ?? '', $config['subject'] ?? '', $config['from'] ?? '');
//                break;
            default:
                $log = new Logger('local');
        }
        $log->setTimezone(new DateTimeZone('Asia/Shanghai'));
        return $log;
    }
}
