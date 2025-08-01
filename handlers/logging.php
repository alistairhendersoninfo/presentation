<?php
// logging.php

class Logger {
    const LEVELS = [
        'DEBUG'     => 10,
        'INFO'      => 20,
        'WARNING'   => 30,
        'ERROR'     => 40,
        'CRITICAL'  => 50,
    ];

    private $logFile;
    private $enabled;
    private $threshold;

    public function __construct($logFile = null) {
        $this->logFile = $logFile ?: dirname(__DIR__) . '/logs/php.log';
        $this->enabled = getenv('LOGGING_ENABLED') === 'true';
        $this->threshold = self::LEVELS[strtoupper(getenv('LOGGING_LEVEL') ?: 'DEBUG')];
    }

    public function log($level, $msg, $context = []) {
        $level = strtoupper($level);
        if (!$this->enabled || self::LEVELS[$level] < $this->threshold) return;
        $date = date('Y-m-d H:i:s');
        $entry = [
            'timestamp' => $date,
            'level' => $level,
            'message' => $msg,
            'context' => $context
        ];
        file_put_contents($this->logFile, json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}

// Global helper
function getLogger() {
    static $logger = null;
    if ($logger === null) $logger = new Logger();
    return $logger;
}
?>
