<?php
/**
 * @file src/Core/Logger.php
 */
namespace Friendica\Core;

use Friendica\App;
use Friendica\BaseObject;
use Monolog;
use Friendica\Util\DateTimeFormat;

/**
 * @brief Logger functions
 */
class Logger extends BaseObject
{
	/**
	 * Creates a basic Monolog instance for logging.
	 *
	 * @param string $application the current application name (index, daemon, ...)
	 *
	 * @return Monolog\Logger The Logger instance
	 */
	public static function create($application)
	{
		$logger = new Monolog\Logger($application);

		$logger->pushProcessor(new Monolog\Processor\IntrospectionProcessor());
		$logger->pushProcessor(new Monolog\Processor\ProcessIdProcessor());
		$logger->pushProcessor(new Monolog\Processor\WebProcessor());
		$logger->pushProcessor(new App\FriendicaLoggerProcessor());

		return $logger;
	}

	/**
	 * Sets the default Logging handler for this instance.
	 * Can be combined with other handlers too if necessary.
	 *
	 * @param Monolog\Logger $logger The Logger instance of this Application
	 * @param App            $app    The Friendica Application
	 */
	public static function loadDefaultHandler($logger, $app)
	{
		foreach ($logger->getProcessors() as $processor) {
			if ($processor instanceof App\FriendicaLoggerProcessor) {
				$processor->setProcessId($app->process_id);
			}
		}

		$debugging = Config::get('system', 'debugging');
		$logfile   = Config::get('system', 'logfile');
		$loglevel = intval(Config::get('system', 'loglevel'));

		if (!$debugging || !$logfile) {
			return;
		}

		$fileHandler = new Monolog\Handler\StreamHandler($logfile . ".1", $loglevel);
		$logger->pushHandler($fileHandler);
	}

    // Log levels:
	//EMERGENCY
	//ALERT
	//CRITICAL
    const WARNING = 0; //ERROR
    const INFO = 1; //WARNING
    const TRACE = 2; //NOTICE(default)
    const DEBUG = 3; //INFO
    const DATA = 4; //INFO
    const ALL = 5; //DEBUG

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
	 *
	 * @deprecated since 2019.03 - use App->getLogger() instead
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
	 *
	 * * @deprecated since 2019.03 - never used
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
