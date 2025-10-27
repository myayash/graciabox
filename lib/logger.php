<?php
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\JsonFormatter;

// Lightweight logger bootstrap. Returns a PSR-3 logger instance.
if (!function_exists('get_logger')) {
    require_once __DIR__ . '/../vendor/autoload.php';

    function get_logger(string $name = 'app'): \Psr\Log\LoggerInterface {
        static $loggers = [];

        if (isset($loggers[$name])) {
            return $loggers[$name];
        }

        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $logger = new Logger($name);

        // Keep 14 days of logs by default
        $handler = new RotatingFileHandler($logDir . '/' . $name . '.log', 14, Logger::DEBUG);
        // Use JSON formatter for structured logs (easier to parse/search)
        $formatter = new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true);
        $handler->setFormatter($formatter);
        $logger->pushHandler($handler);

        // Add processors to enrich records with useful context (only if available)
        $processorClass = 'Monolog\\Processor\\UidProcessor';
        if (class_exists($processorClass)) {
            $logger->pushProcessor((new \ReflectionClass($processorClass))->newInstance());
        }

        $processorClass = 'Monolog\\Processor\\WebProcessor';
        if (class_exists($processorClass)) {
            $logger->pushProcessor((new \ReflectionClass($processorClass))->newInstance());
        }

        // IntrospectionProcessor adds file/line where the log call was made
        $processorClass = 'Monolog\\Processor\\IntrospectionProcessor';
        if (class_exists($processorClass)) {
            $logger->pushProcessor((new \ReflectionClass($processorClass))->newInstance());
        }
        // Keep the old simple processor too for explicit fields
        $logger->pushProcessor(function ($record) {
            $record['extra']['request_id'] = $_SERVER['REQUEST_ID'] ?? null;
            $record['extra']['remote_addr'] = $_SERVER['REMOTE_ADDR'] ?? null;
            $record['extra']['user_id'] = $_SESSION['user_id'] ?? null;
            $record['extra']['username'] = $_SESSION['username'] ?? null;
            return $record;
        });

        $loggers[$name] = $logger;
        return $logger;
    }
}
