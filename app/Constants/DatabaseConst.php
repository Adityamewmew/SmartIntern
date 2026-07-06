<?php

namespace App\Constants;

class DatabaseConst
{
    const SQL_READ = 'mysql_read';

    public static function USER(): string
    {
        return self::DB_CORE().'.users';
    }

    public static function SIDEBAR_MENU(): string
    {
        return self::DB_CORE().'.sidebar_menus';
    }

    public static function SIDEBAR_MENU_ACCESS(): string
    {
        return self::DB_CORE().'.sidebar_menu_accesses';
    }

    public static function SIDEBAR_MENU_GROUP(): string
    {
        return self::DB_CORE().'.sidebar_menu_groups';
    }

    public static function DAILY_LOG(): string
    {
        return self::DB_CORE().'.daily_logs';
    }

    public static function DAILY_LOG_IMAGE(): string
    {
        return self::DB_CORE().'.daily_log_images';
    }

    public static function DB_CORE(): string
    {
        return config('database.connections.mysql.database', 'default');
    }
}
