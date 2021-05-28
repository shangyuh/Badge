# badge
徽章组件

徽章组件说明.

使用此组件，一定要保证当日数据已经初始化，否则相关的添加与删除是无法成功的.

使用redis的配置:\Config\Badge::$redis 配置徽章数据存入redis的相关设置(Redis实例名称、Redis的key前缀).

初始话配置:\Config\Badge::$initCfg 存入所有徽章路径，并确认每个位置数据来源(total:所有子路径的统计数据;virtual:读取配置的虚拟数据;select:外部的查询数据;).

徽章分类配置:\Config\Badge::$sortCfg 存入所有徽章的展示分类属性，并确认展示的优先级(red_dot:红点展示;count:数字展示;text:文本展示;),优先级根据从左至右的展示顺序(例如:'applet.root.task' => 'text|red_dot|count',意思就是存在文案优先展示,没有文案则看小红点,前面两者都没有就默认展示数字).

徽章红点分类索引配置:\Config\Badge::$redDotSortMapCfg 展示红点分类属性的所有徽章.

徽章计数分类索引配置:\Config\Badge::$countSortMapCfg 展示数字分类属性的所有徽章(该其实是所有徽章都有的一个默认初始数字,因为默认最后都是数字展示,redis也是记录的纯数字).

徽章文本分类索引配置:\Config\Badge::$textSortMapCfg 展示文本分类属性的所有徽章(如果存在数据,并且是文本展示类型,满足展示就从中获取展示文案).
