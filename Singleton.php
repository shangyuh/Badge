<?php
/**
 * Singleton.
 *
 * @author shangyuh<shangyuh@jumei.com>
 */

namespace Badge;

/**
 * 单例.
 */
abstract class Singleton {

    protected static $instances;

    /**
     * Get instance of the derived class.
     *
     * @return \Badge\Singleton
     */
    public static function instance()
    {
        $className = get_called_class();
        if (!isset(self::$instances[$className])) {
            self::$instances[$className] = new $className;
        }
        return self::$instances[$className];
    }

}
