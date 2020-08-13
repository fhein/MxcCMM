<?php

namespace MxcCommons\Plugin\Service;

use Throwable;
use Traversable;
use MxcCommons\Log\Logger as BaseLogger;

class Logger implements LoggerInterface
{
    /**
     * @var BaseLogger $log
     */
    protected $log;
    /**
     * @var int $indent
     */
    protected $indent = 0;

    /**
     * @var int $indentSize
     */
    protected $indentSize;
    /**
     * @var string $enterMarker
     */
    protected $enterMarker;
    /**
     * @var string $leaveMarker
     */
    protected $leaveMarker;

    public function __construct(BaseLogger $log, int $indentSize = 1, string $enterMarker = '>>>', string $leaveMarker = '<<<') {
        $this->log = $log;
        $this->indentSize = $indentSize;
        $this->enterMarker = $enterMarker;
        $this->leaveMarker = $leaveMarker;
    }

    protected function indentMessage(string $msg) {
        return str_repeat(' ', $this->indent) . $msg;
    }

    /**
     * @param string $message
     * @param array|Traversable $extra
     * @return LoggerInterface
     */
    public function emerg($message, $extra = [])
    {
        $this->log->emerg($this->indentMessage($message), $extra);
        return $this;
    }

    /**
     * @param string $message
     * @param array|Traversable $extra
     * @return LoggerInterface
     */
    public function alert($message, $extra = [])
    {
        $this->log->alert($this->indentMessage($message), $extra);
        return $this;
    }

    /**
     * @param string $message
     * @param array|Traversable $extra
     * @return LoggerInterface
     */
    public function crit($message, $extra = [])
    {
        $this->log->crit($this->indentMessage($message), $extra);
        return $this;
    }

    /**
     * @param string $message
     * @param array|Traversable $extra
     * @return LoggerInterface
     */
    public function err($message, $extra = [])
    {
        $this->log->err($this->indentMessage($message), $extra);
        return $this;
    }

    /**
     * @param string $message
     * @param array|Traversable $extra
     * @return LoggerInterface
     */
    public function warn($message, $extra = [])
    {
        $this->log->warn($this->indentMessage($message), $extra);
        return $this;
    }

    /**
     * @param string $message
     * @param array|Traversable $extra
     * @return LoggerInterface
     */
    public function notice($message, $extra = [])
    {
        $this->log->notice($this->indentMessage($message), $extra);
        return $this;
    }

    /**
     * @param string $message
     * @param array|Traversable $extra
     * @return LoggerInterface
     */
    public function info($message, $extra = [])
    {
        $this->log->info($this->indentMessage($message), $extra);
        return $this;
    }

    /**
     * @param string $message
     * @param array|Traversable $extra
     * @return LoggerInterface
     */
    public function debug($message, $extra = [])
    {
        $this->log->debug($this->indentMessage($message), $extra);
        return $this;
    }

    public function enter() {
        $result = $this->logAction($this->enterMarker);
        $this->indent += $this->indentSize;
        return $result;
    }

    public function leave() {
        $this->indent -= $this->indentSize;
        $this->indent = max($this->indent, 0);
        return $this->logAction($this->leaveMarker);
    }

    public function except(Throwable $e, bool $logTrace = true, bool $rethrow = true) {
        $this->emerg(sprintf('%s: %s', get_class($e), $e->getMessage()));
        if ($logTrace) $this->emerg('Call stack: ' . PHP_EOL . $e->getTraceAsString());
        if ($rethrow) throw($e);
        return $this;
    }

    protected function logAction(string $marker = '') {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3)[2];
        $this->debug(sprintf('%s %s#%s', $marker, $trace['class'], $trace['function']));
    }
}