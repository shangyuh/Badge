<?php
/*
 * 徽章组件说明.
 * 使用此组件，一定要保证当日数据已经初始化，否则相关的添加与删除是无法成功的.
 * 使用redis的配置:\Config\Badge::$redis 配置徽章数据存入redis的相关设置(Redis实例名称、Redis的key前缀).
 * 初始话配置:\Config\Badge::$initCfg 存入所有徽章路径，并确认每个位置数据来源(total:所有子路径的统计数据;virtual:读取配置的虚拟数据;select:外部的查询数据;).
 * 徽章分类配置:\Config\Badge::$sortCfg 存入所有徽章的展示分类属性，并确认展示的优先级(red_dot:红点展示;count:数字展示;text:文本展示;),优先级根据从左至右的展示顺序(例如:'applet.root.task' => 'text|red_dot|count',意思就是存在文案优先展示,没有文案则看小红点,前面两者都没有就默认展示数字).
 * 徽章红点分类索引配置:\Config\Badge::$redDotSortMapCfg 展示红点分类属性的所有徽章.
 * 徽章计数分类索引配置:\Config\Badge::$countSortMapCfg 展示数字分类属性的所有徽章(该其实是所有徽章都有的一个默认初始数字,因为默认最后都是数字展示,redis也是记录的纯数字).
 * 徽章文本分类索引配置:\Config\Badge::$textSortMapCfg 展示文本分类属性的所有徽章(如果存在数据,并且是文本展示类型,满足展示就从中获取展示文案).
 */

$uid = 123456789;

// 初始化当日徽章数据 不返回初始数据.
$a1 = \Badge\Badge::instance()->initInfo($uid);
var_dump($a1); // true初始化成功,false初始化失败.
// 初始化当日徽章数据 返回初始数据.
$a2 = \Badge\Badge::instance()->initInfo($uid, true);
var_dump($a2); // 返回数组信息初始化成功,返回空数组初始化失败.

// 获取自选徽章信息.
$types = 'applet.root.home'; // 多个$types = 'applet.root.home, applet.root.me'.
$b1 = \Badge\Badge::instance()->getInfoByTypes($uid, $types);
var_dump($b1); // 返回传入的徽章的redis信息的数组.
$types = array('applet.root.home'); // 多个$types = array('applet.root.home', 'applet.root.me').
$b2 = \Badge\Badge::instance()->getInfoByTypes($uid, $types);
var_dump($b2); // 返回传入的徽章的redis信息的数组.

// 根据多个徽章获取所有路径上徽章信息.
$types = 'applet.root.home'; // 多个$types = 'applet.root.home, applet.root.me'.
$c1 = \Badge\Badge::instance()->getInfoFromUrls($uid, $types);
var_dump($c1); // 返回传入的徽章的所有路径redis信息的数组.
$types = array('applet.root.home'); // 多个$types = array('applet.root.home', 'applet.root.me').
$c2 = \Badge\Badge::instance()->getInfoFromUrls($uid, $types);
var_dump($c2); // 返回传入的徽章的所有路径redis信息的数组.

// 添加多个徽章信息(操作为加法).
$types = array('applet.root.home' => 1); // 多个$types = array('applet.root.home' => 1, 'applet.root.me' => 8).
$d = \Badge\Badge::instance()->addInfos($uid, $types);
var_dump($d); // 返回成功操作的数据.

// 更新多个徽章信息(操作为减法).
$types = array('applet.root.home' => 1); // 多个$types = array('applet.root.home' => 1, 'applet.root.me' => 8).
$e = \Badge\Badge::instance()->updateInfos($uid, $types);
var_dump($e); // 返回成功操作的数据.


// 配置存入格式说明.
/*
\Config\Badge::$redis 配置徽章数据存入redis的相关设置(Redis实例名称、Redis的key前缀).
public static $redis = array(
    'name' => 'default', // Redis实例名称.
    'prefix' => 'badge_', // Redis的key前缀.
);

\Config\Badge::$initCfg 存入所有徽章路径，并确认每个位置数据来源(total:所有子路径的统计数据;virtual:读取配置的虚拟数据;select:外部的查询数据;).
public static $initCfg = array(
    'applet' => 'total',
    'applet.root' => 'total',
    'applet.root.home' => 'total',
    'applet.root.task' => 'total',
    'applet.root.me' => 'total',
    'applet.root.task.sign_in' => 'select',
);

\Config\Badge::$sortCfg 存入所有徽章的展示分类属性，并确认展示的优先级(red_dot:红点展示;count:数字展示;text:文本展示;),优先级根据从左至右的展示顺序(例如:'applet.root.task' => 'text|red_dot|count',意思就是存在文案优先展示,没有文案则看小红点,前面两者都没有就默认展示数字).
array(
    'applet' => 'count',
    'applet.root' => 'count',
    'applet.root.home' => 'count',
    'applet.root.task' => 'text|count|red_dot',
    'applet.root.me' => 'count',
    'applet.root.task.sign_in' => 'text',
);

\Config\Badge::$redDotSortMapCfg 展示红点分类属性的所有徽章.
array(
    'applet.root.task',
);

\Config\Badge::$countSortMapCfg 展示数字分类属性的所有徽章(该其实是所有徽章都有的一个默认初始数字,因为默认最后都是数字展示,redis也是记录的纯数字).
array(
    'applet' => 0,
    'applet.root' => 0,
    'applet.root.home' => 0,
    'applet.root.task' => 0,
    'applet.root.me' => 0,
);

\Config\Badge::$textSortMapCfg 展示文本分类属性的所有徽章(如果存在数据,并且是文本展示类型,满足展示就从中获取展示文案).
array(
    'applet.root.task.sign_in' => '待领取',
);
*/


