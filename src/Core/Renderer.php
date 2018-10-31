<?php
/**
 * @file src/Core/Renderer.php
 */

namespace Friendica\Core;

use Exception;
use Friendica\BaseObject;
use Friendica\Core\System;
use Friendica\Render\FriendicaSmarty;

/**
 * @brief This class handles Renderer related functions.
 */
class Renderer extends BaseObject
{
    private static $ldelim = [
		'internal' => '',
		'smarty3' => '{{'
	];
	private static $rdelim = [
		'internal' => '',
		'smarty3' => '}}'
    ];
    
    /**
     * @brief This is our template processor
     *
     * @param string|FriendicaSmarty $s The string requiring macro substitution or an instance of FriendicaSmarty
     * @param array $r                  key value pairs (search => replace)
     * 
     * @return string substituted string
    */
    public static function replaceMacros($s, $r)
    {
        $stamp1 = microtime(true);
        $a = self::getApp();

        // pass $baseurl to all templates
        $r['$baseurl'] = System::baseUrl();
        $t = $a->getTemplateEngine();

        try {
            $output = $t->replaceMacros($s, $r);
        } catch (Exception $e) {
            echo "<pre><b>" . __FUNCTION__ . "</b>: " . $e->getMessage() . "</pre>";
            killme();
        }

        $a->saveTimestamp($stamp1, "rendering");

        return $output;
    }

    /**
     * @brief Load a given template $s
     *
     * @param string $s     Template to load.
     * @param string $root  Optional.
     * 
     * @return string template.
     */
    public static function getMarkupTemplate($s, $root = '')
    {
        $stamp1 = microtime(true);
        $a = self::getApp();
        $t = $a->getTemplateEngine();

        try {
            $template = $t->getTemplateFile($s, $root);
        } catch (Exception $e) {
            echo "<pre><b>" . __FUNCTION__ . "</b>: " . $e->getMessage() . "</pre>";
            killme();
        }

        $a->saveTimestamp($stamp1, "file");

        return $template;
    }

    /**
	 * Gets the right delimiter for a template engine
	 *
	 * Currently:
	 * Internal = ''
	 * Smarty3 = '{{'
	 *
	 * @param string $engine The template engine (default is Smarty3)
	 *
	 * @return string the right delimiter
	 */
	public static function getTemplateLeftDelimiter($engine = 'smarty3')
	{
		return self::$ldelim[$engine];
	}

	/**
	 * Gets the left delimiter for a template engine
	 *
	 * Currently:
	 * Internal = ''
	 * Smarty3 = '}}'
	 *
	 * @param string $engine The template engine (default is Smarty3)
	 *
	 * @return string the left delimiter
	 */
	public static function getTemplateRightDelimiter($engine = 'smarty3')
	{
		return self::$rdelim[$engine];
	}
}
