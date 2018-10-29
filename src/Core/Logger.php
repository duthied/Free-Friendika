<?php
/**
 * @file src/Core/Logger.php
 */
namespace Friendica\Core;

use Friendica\Core\Config;
use Friendica\Util\DateTimeFormat;

class Logger
{
    /**
     * @brief Logs the given message at the given log level
     *
     * log levels:
     * LOGGER_WARNING
     * LOGGER_INFO (default)
     * LOGGER_TRACE
     * LOGGER_DEBUG
     * LOGGER_DATA
     * LOGGER_ALL
     *
     * @global array $LOGGER_LEVELS
     * @param string $msg
     * @param int $level
     */
    public static function log($msg, $level = LOGGER_INFO)
    {
        $a = get_app();
        global $LOGGER_LEVELS;
        $LOGGER_LEVELS = [];

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

        if (count($LOGGER_LEVELS) == 0) {
            foreach (get_defined_constants() as $k => $v) {
                if (substr($k, 0, 7) == "LOGGER_") {
                    $LOGGER_LEVELS[$v] = substr($k, 7, 7);
                }
            }
        }

        $process_id = session_id();

        if ($process_id == '') {
            $process_id = get_app()->process_id;
        }

        $callers = debug_backtrace();

        if (count($callers) > 1) {
            $function = $callers[1]['function'];
        } else {
            $function = '';
        }

        $logline = sprintf("%s@%s\t[%s]:%s:%s:%s\t%s\n",
                DateTimeFormat::utcNow(DateTimeFormat::ATOM),
                $process_id,
                $LOGGER_LEVELS[$level],
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
     * log levels:
     * LOGGER_WARNING
     * LOGGER_INFO (default)
     * LOGGER_TRACE
     * LOGGER_DEBUG
     * LOGGER_DATA
     * LOGGER_ALL
     *
     * @global array $LOGGER_LEVELS
     * @param string $msg
     * @param int $level
     */
    public static function devLog($msg, $level = LOGGER_INFO)
    {
        $a = get_app();

        $logfile = Config::get('system', 'dlogfile');
        if (!$logfile) {
            return;
        }

        $dlogip = Config::get('system', 'dlogip');
        if (!is_null($dlogip) && $_SERVER['REMOTE_ADDR'] != $dlogip) {
            return;
        }

        if (count($LOGGER_LEVELS) == 0) {
            foreach (get_defined_constants() as $k => $v) {
                if (substr($k, 0, 7) == "LOGGER_") {
                    $LOGGER_LEVELS[$v] = substr($k, 7, 7);
                }
            }
        }

        $process_id = session_id();

        if ($process_id == '') {
            $process_id = $a->process_id;
        }

        $callers = debug_backtrace();
        $logline = sprintf("%s@\t%s:\t%s:\t%s\t%s\t%s\n",
                DateTimeFormat::utcNow(),
                $process_id,
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