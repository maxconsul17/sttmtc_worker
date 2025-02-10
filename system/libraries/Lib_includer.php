<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2011, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

class CI_Lib_includer
{
	/**
	 * Constructor
	 *
	 * @param	string $class class name
	 */
	function __construct($class = NULL)
	{
		// include path for Lib_includer Framework
		// alter it accordingly if you have put the 'Lib_includer' folder elsewhere
		ini_set('include_path',
		ini_get('include_path') . PATH_SEPARATOR . APPPATH . 'libraries');

		if ($class)
		{
			require_once (string) $class.".php";
			log_message('debug', "Lib_includer Class $class Loaded");
		}
		else
		{
			log_message('debug', "Lib_includer Class Initialized");
		}
	}

	/**
	 * Zend Class Loader
	 *
	 * @param	string $class class name
	 */
	function load($class)
	{
		require_once (string) $class.".php";
		log_message('debug', "Lib_includer Class $class Loaded");
	}
}
// END Excel_generator class

/* End of file Lib_includer.php */
/* Location: ./system/libraries/Excel_generator.php */