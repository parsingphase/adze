<?php
/**
 * Created by PhpStorm.
 * User: parsingphase
 * Date: 06/09/14
 * Time: 21:28
 */

namespace Phase\Adze\Exception;


use Exception;

/**
 * Thrown when we needed an Adze\Application but got something else (probably a Silex\Application)
 *
 * Required in some cases where Silex Interface specifications mean we have to type-hint as Silex\Application
 *
 * @package Phase\Adze\Exception
 */
class UnpromotedApplicationException extends Exception
{

}
