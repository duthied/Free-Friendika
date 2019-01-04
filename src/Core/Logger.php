<?php
/**
 * @file src/Core/Logger.php
 */
namespace Friendica\Core;

use Friendica\BaseObject;
use Friendica\Core\Config;
use Friendica\Util\DateTimeFormat;
use ReflectionClass;

/**
 * @brief Logger functions
 */
class Logger extends BaseObject
{
    // Log levels:
    const WARNING = 0;
    const INFO = 1;
    const TRACE = 2;
    const DEBUG = 3;
    const DATA = 4;
    const ALL = 5;

    public static $levels = [
        self::WARNING => 'Warning',
        self::INFO => 'Info',
        self::TRACE => 'Trace',
        self::DEBUG => 'Debug',
        self::DATA => 'Data',
        self::ALL => 'All',
    ];

    /**
     * @brief Logs the given message at the given log level
     *
     * @param string $msg
     * @param int $level
     */
    public static function log($msg, $level = self::INFO)
    {
        $a = self::getApp();

        $debugging = Config::get('system', 'debugging');
        $logfile   = Config::get('system', 'logfile');
        $loglevel = intval(Config::get('system', 'loglevel'));

        if (
            !$debugging
            || !$logfile
            || $level > $loglevel
        ) {
            return;
        }

        $processId = session_id();

        if ($processId == '')
        {
            $processId = $a->process_id;
        }

        $callers = debug_backtrace();

        if (count($callers) > 1) {
            $function = $callers[1]['function'];
        } else {
            $function = '';
        }

        $logline = sprintf("%s@%s\t[%s]:%s:%s:%s\t%s\n",
                DateTimeFormat::utcNow(DateTimeFormat::ATOM),
                $processId,
                self::$levels[$level],
                basename($callers[0]['file']),
                $callers[0]['line'],
                $function,
                $msg
            );

        $stamp1 = microtime(true);
        @file_put_contents($logfile, $logline, FILE_APPEND);
        $a->saveTimestamp($stamp1, "file");
    }

    /**
     * @brief An alternative logger for development.
     * Works largely as log() but allows developers
     * to isolate particular elements they are targetting
     * personally without background noise
     *
     * @param string $msg
     */
    public static function devLog($msg)
    {
        $a = self::getApp();

        $logfile = Config::get('system', 'dlogfile');

        if (!$logfile) {
            return;
        }

        $dlogip = Config::get('system', 'dlogip');

        if (!is_null($dlogip) && $_SERVER['REMOTE_ADDR'] != $dlogip)
        {
            return;
        }

        $processId = session_id();

        if ($processId == '')
        {
            $processId = $a->process_id;
        }

        if (!is_string($msg)) {
        	$msg = var_export($msg, true);
        }

        $callers = debug_backtrace();
        $logline = sprintf("%s@\t%s:\t%s:\t%s\t%s\t%s\n",
                DateTimeFormat::utcNow(),
                $processId,
                basename($callers[0]['file']),
                $callers[0]['line'],
                $callers[1]['function'],
                $msg
            );

        $stamp1 = microtime(true);
        @file_put_contents($logfile, $logline, FILE_APPEND);
        $a->saveTimestamp($stamp1, "file");
    }
}
