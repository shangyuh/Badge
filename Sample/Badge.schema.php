<?php
namespace Config;

class Badge
{

    /**
     * 使用redis的配置.
     *
     * @var array
     */
    public static $redis = array(
        'name' => 'default', // Redis实例名称.
        'prefix' => 'badge_', // Redis的key前缀.
    );

    /**
     * 初始话配置.
     *
     * @var array
     */
    public static $initCfg = array(
        'applet' => 'total',
        'applet.root' => 'total',
        'applet.root.home' => 'total',
        'applet.root.task' => 'total',
        'applet.root.me' => 'total',
        'applet.root.task.sign_in' => 'select',
    );

    /**
     * 徽章分类配置.
     *
     * @var array
     */
    public static $sortCfg = array(
        'applet' => 'count',
        'applet.root' => 'count',
        'applet.root.home' => 'count',
        'applet.root.task' => 'text|count|red_dot',
        'applet.root.me' => 'count',
        'applet.root.task.sign_in' => 'text',
    );

    /**
     * 徽章红点分类索引配置.
     *
     * @var array
     */
    public static $redDotSortMapCfg = array(
        'applet.root.task',
    );

    /**
     * 徽章计数分类索引配置.
     *
     * @var array
     */
    public static $countSortMapCfg = array(
        'applet' => 0,
        'applet.root' => 0,
        'applet.root.home' => 0,
        'applet.root.task' => 0,
        'applet.root.me' => 0,
    );

    /**
     * 徽章文本分类索引配置.
     *
     * @var array
     */
    public static $textSortMapCfg = array(
        'applet.root.task.sign_in' => '待领取',
    );

}