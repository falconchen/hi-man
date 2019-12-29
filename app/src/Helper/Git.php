<?php

namespace App\Helper;

/**
 * some operation via git
 */
class Git
{
	static $logObj = null;
	/**
	 * get the latest log
	 *
	 * @return stdClass
	 */

	static function latestLog()
	{
		if (null === self::$logObj) {
			$cmd = "git log -1";
			exec($cmd . "  2>&1", $output, $return_val);
			$obj = new \stdClass();
			$obj->output = $output;
			foreach ($output as $value) {
				preg_match("/Date:(.*)/i", $value, $match);
				if (!empty($match[1])) {
					$obj->date = strtotime($match[1]);
					$obj->dateString = trim($match[1]);
					$obj->dateMySQL = date("Y-m-d H:i:s", $obj->date);
					$obj->dateVer = date('ymdHis', $obj->date);
				}
			}
			self::$logObj = $obj;
		}

		return self::$logObj;
	}
}
