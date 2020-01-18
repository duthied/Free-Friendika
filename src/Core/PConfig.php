<?php
/**
 * User Configuration Class
 *
 * @file include/Core/PConfig.php
 *
 * @brief Contains the class with methods for user configuration
 */
namespace Friendica\Core;

use Friendica\DI;

/**
 * @brief Management of user configuration storage
 * Note:
 * Please do not store booleans - convert to 0/1 integer values
 * The DI::pConfig()->get() functions return boolean false for keys that are unset,
 * and this could lead to subtle bugs.
 */
class PConfig
{
}
