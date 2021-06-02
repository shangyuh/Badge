<?php
/**
 * Badge Package.
 *
 * @author shangyuh<shangyuh@jumei.com>
 */

namespace Badge;

/**
 * Badge Package.
 */
class Badge extends \Badge\Singleton
{

    /**
     * Get instance of the derived class.
     *
     * @return \Badge\Badge
     */
    public static function instance()
    {
        return parent::instance();
    }

    /**
     * 获取徽章缓存key.
     *
     * @param integer $uid 用户ID.
     *
     * @return string
     */
    private function getCacheKey($uid)
    {
        return $uid ? \Config\Badge::$redis['prefix'] . $uid : '';
    }

    /**
     * 获取redis.
     *
     * @param integer $uid 用户ID.
     *
     * @return \Redis\RedisCache&\Redis
     */
    private function getRedis($uid)
    {
        return \Redis\RedisMultiCache::getInstance(\Config\Badge::$redis['name'])->partitionByUID($uid);
    }

    /**
     * 初始化当日徽章数据.
     *
     * @param integer $uid       用户id.
     * @param boolean $isCheck   是否只检查初始化状态(true:只检查;false:没有初始化时进行初始化).
     * @param boolean $isResData 是否返回数据.
     *
     * @return boolean|array
     * @throws \Exception 异常信息
     */
    private function initRedisInfo($uid, $isCheck = false, $isResData = false)
    {
        $result = false;
        $data = array();
        $cacheKey = $this->getCacheKey($uid);
        if ($cacheKey) {
            $redis = $this->getRedis($uid);
            if ($redis->hGet($cacheKey, 'date') != date('Y-m-d')) {
                if ($isCheck) {
                    // 没有初始化今日的数据.
                    return $result;
                }
                $initCfg = isset(\Config\Badge::$initCfg) ? \Config\Badge::$initCfg : array();
                $countSortMapCfg = isset(\Config\Badge::$countSortMapCfg) ? \Config\Badge::$countSortMapCfg : array();
                if (!empty($initCfg)) {
                    $data['date'] = date('Y-m-d');
                    foreach ($initCfg as $type => $dataOperate) {
                        // 统计数据初始化.
                        if ($dataOperate == 'total') {
                            $data[$type] = isset($countSortMapCfg[$type]) ? $countSortMapCfg[$type] : 0;
                        }
                        // 虚拟数据初始化.
                        if ($dataOperate == 'virtual') {
                            $data[$type] = isset($countSortMapCfg[$type]) ? $countSortMapCfg[$type] : 0;
                        }
                        // 查询数据初始化.
                        if ($dataOperate == 'select') {
                            // 需要外部数据支持,默认初始为配置中的值.
                            $data[$type] = isset($countSortMapCfg[$type]) ? $countSortMapCfg[$type] : 0;
                            // 根据临时数据的信息来确认外部数据最终的值.
                            $tempData = $redis->hGet($cacheKey, 'temp_data');
                            $tempData = empty($tempData) ? array() : json_decode($tempData, true);
                            $data[$type] = isset($tempData[$type]) ? $tempData[$type] : $data[$type];
                        }
                    }
                    $data = $this->totalInfo($data);
                    $saveRes = $this->saveRedisInfo($uid, $data, 'add', true);
                    if (!empty($saveRes)) {
                        $result = true;
                    }
                }
            } else {
                $result = true;
                if ($isCheck) {
                    // 今日的数据已初始化.
                    return $result;
                }
                $data = $isResData ? $redis->hGetAll($cacheKey) : array();
            }
        }
        return $result && $isResData ? $this->formatInfo($uid, $data) : $result;
    }

    /**
     * 数据统计.
     *
     * @param array $data 数据.
     *
     * @return array
     */
    private function totalInfo(array $data)
    {
        foreach ($data as $type => $num) {
            $total = substr_count($type, '.');
            if ($total > 0) {
                $typeTemp = $type;
                for ($i = 0; $i < $total; $i++) {
                    $pos = strrpos($typeTemp, '.', -1);
                    $typeTemp = substr($type, 0, $pos);
                    $data[$typeTemp] = $data[$typeTemp] + $num;
                }
            }
        }
        return $data;
    }

    /**
     * 获取徽章数据.
     *
     * @param integer $uid      用户id.
     * @param array   $hashKeys 具体查询的hashKeys,不传查所有.
     * @param boolean $isFormat 是否格式化返回数据.
     *
     * @return array
     * @throws \Exception 异常信息
     */
    private function getRedisInfo($uid, array $hashKeys = array(), $isFormat = false)
    {
        $result = array();
        $cacheKey = $this->getCacheKey($uid);
        if ($cacheKey) {
            $redis = $this->getRedis($uid);
            if ($this->initRedisInfo($uid, true)) {
                $data = empty($hashKeys) ? $redis->hGetAll($cacheKey) : $redis->hMGet($cacheKey, $hashKeys);
                if ($data) {
                    $result = $isFormat ? $this->formatInfo($uid, $data) : $data;
                }
            }
        }
        return $result;
    }

    /**
     * 增加徽章数据.
     *
     * @param string  $uid     用户id.
     * @param array   $data    数据.
     * @param string  $operate 操作类型(add:添加;update:更新).
     * @param boolean $isInit  是否初始化写入(慎重使用).
     *
     * @return array
     * @throws \Exception 异常信息
     */
    private function saveRedisInfo($uid, array $data, $operate, $isInit = false)
    {
        $cacheKey = $this->getCacheKey($uid);
        if ($cacheKey) {
            $redis = $this->getRedis($uid);
            if ($this->initRedisInfo($uid, true) || $isInit) {
                if ($isInit) {
                    $redis->del($cacheKey);
                    // 初始化时检查当日日期是否有存入.
                    $data['date'] = empty($data['date']) ? date('Y-m-d') : $data['date'];
                } else {
                    $data = $this->dealInfo($uid, $data, $operate);
                }
                $result = $redis->hMSet($cacheKey, $data);
            } else {
                // 今日没有初始化数据，将外部写入数据放入临时字段中.
                $tempData = $redis->hGet($cacheKey, 'temp_data');
                $tempData = empty($tempData) ? array() : json_decode($tempData, true);
                $tempData = $this->saveRedisTempData($tempData, $data, $operate);
                $redis->hSet($cacheKey, 'temp_data', json_encode($tempData));
            }
        }
        return empty($result) ? array() : $data;
    }

    /**
     * 数据添加或更新处理.
     *
     * @param integer $uid     用户id.
     * @param array   $data    数据.
     * @param string  $operate 操作类型.
     *
     * @return array
     * @throws \Exception 异常信息
     */
    private function dealInfo($uid, array $data, $operate)
    {
        // 获取所有路径的redis值.
        $allData = $this->getSubpathInfo($uid, $data, true);
        if ($operate == 'add') {
            // 添加数据(加法).
            foreach ($allData as $aKey => &$aNum) {
                foreach ($data as $type => $num) {
                    if (strpos($type, $aKey) !==false) {
                        $addendNum = (integer)$num;
                        $aNum = $aNum + $addendNum;
                    }
                }
            }
        } elseif ($operate == 'update') {
            // 更新数据(减法).
            foreach ($allData as $aKey => &$aNum) {
                foreach ($data as $type => $num) {
                    if (strpos($type, $aKey) !==false) {
                        $minusNum = (integer)$num > $allData[$type] ? $allData[$type] : (integer)$num;
                        $aNum = $aNum - $minusNum;
                        // 减成负数就置为0.
                        $aNum = $aNum < 0 ? 0 : $aNum;
                    }
                }
            }
        }
        return $allData;
    }

    /**
     * 记录临时数据(今日未初始化的外部数据).
     *
     * @param array   $oldData 原数据.
     * @param array   $newData 新数据.
     * @param string  $operate 操作类型.
     *
     * @return array
     * @throws \Exception 异常信息
     */
    private function saveRedisTempData(array $oldData, array $newData, $operate)
    {
        $data = $oldData;
        if ($operate == 'add') {
            // 添加数据(加法).
            foreach ($newData as $aKey => $aNum) {
                if (isset($data[$aKey])) {
                    $addendNum = (integer)$data[$aKey];
                    $data[$aKey] = (integer)$aNum + $addendNum;
                } else {
                    $data[$aKey] = (integer)$aNum;
                }
            }
        } elseif ($operate == 'update') {
            // 更新数据(减法).
            foreach ($newData as $aKey => $aNum) {
                if (isset($data[$aKey])) {
                    $num = (integer)$data[$aKey]; // 被减数.
                    $minusNum = (integer)$aNum > $num ? $num : (integer)$aNum; // 减数.
                    $data[$aKey] = $num - $minusNum; // 差.
                    // 减成负数就置为0.
                    $data[$aKey] = $data[$aKey] < 0 ? 0 : $data[$aKey];
                } else {
                    $data[$aKey] = 0;
                }
            }
        }
        return $data;
    }

    /**
     * 根据子路径获取上级所有路径的redis信息.
     *
     * @param integer $uid         用户id.
     * @param array   $data        数据.
     * @param boolean $isRedisData 是否返回redis数据.
     * @param boolean $isFormat    是否格式化返回数据.
     *
     * @return array
     * @throws \Exception 异常信息
     */
    private function getSubpathInfo($uid, array $data, $isRedisData = false, $isFormat = false)
    {
        // 获取所有路径.
        $allPaths = array();
        foreach ($data as $type => $num) {
            $total = substr_count($type, '.');
            if ($total > 0) {
                $typeTemp = $type;
                for ($i = 0; $i < $total; $i++) {
                    $pos = strrpos($typeTemp, '.', -1);
                    $typeTemp = substr($type, 0, $pos);
                    $allPaths[] = $typeTemp;
                }
            }
            $allPaths[] = $type;
        }
        sort($allPaths);
        return $isRedisData ? $this->getInfoByTypes($uid, $allPaths, $isFormat) : $allPaths;
    }

    /**
     * 格式化徽章数据展示.
     *
     * @param integer $uid  用户id.
     * @param array   $data 数据.
     *
     * @return array
     * @throws \Exception 异常信息
     */
    private function formatInfo($uid, array $data)
    {
        $sortCfg = isset(\Config\Badge::$sortCfg) ? \Config\Badge::$sortCfg : array();
        $redDotSortMapCfg = isset(\Config\Badge::$redDotSortMapCfg) ? \Config\Badge::$redDotSortMapCfg : array();
        $countSortMapCfg = isset(\Config\Badge::$countSortMapCfg) ? \Config\Badge::$countSortMapCfg : array();
        $textSortMapCfg = isset(\Config\Badge::$textSortMapCfg) ? \Config\Badge::$textSortMapCfg : array();
        foreach ($data as $type => $num) {
            if (in_array($type, array('date', 'temp_data'))) {
                // 特殊字段不处理.
                continue;
            }
            $sorts = explode('|', $sortCfg[$type]);
            foreach ($sorts as $sort) {
                if ($sort == 'red_dot') {
                    if ($num > 0 && in_array($type, $redDotSortMapCfg)) {
                        $data[$type] = true;
                        break;
                    }
                }
                if ($sort == 'count') {
                    if ($num > 0 && isset($countSortMapCfg[$type])) {
                        $data[$type] = (integer)$num;
                        break;
                    }
                }
                if ($sort == 'text') {
                    if ($num > 0 && !empty($textSortMapCfg[$type])) {
                        $data[$type] = $textSortMapCfg[$type];
                        break;
                    }
                }
                // 默认返回数字型.
                $data[$type] = (integer)$num;
            }
        }
        // 检查非数字展示是否向上级路径覆盖.
        foreach ($data as $key => $value) {
            if (in_array($type, array('date', 'temp_data'))) {
                // 日期不处理.
                continue;
            }
            if (!is_numeric($value)
                && !empty($value)) {
                $temp = array_diff($this->getSubpathInfo($uid, array($key => 0)), array($key));
                if (!empty($temp)) {
                    foreach ($temp as $v) {
                        if (isset($data[$v]) && is_numeric($data[$v])) {
                            $sorts = explode('|', $sortCfg[$v]);
                            $sort = $sorts[0];
                            if (in_array($sort, array('red_dot', 'text'))){
                                $data[$v] = $value;
                            }
                        }
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 初始化当日徽章数据.
     *
     * @param integer $uid       用户id.
     * @param boolean $isCheck   是否只检查初始化状态(true:只检查;false:没有初始化时进行初始化).
     * @param boolean $isResData 是否返回数据.
     *
     * @return boolean|array
     * @throws \Exception 异常信息
     */
    public function initInfo($uid, $isCheck = false, $isResData = false)
    {
        return $this->initRedisInfo($uid, $isCheck, $isResData);
    }

    /**
     * 获取自选徽章信息.
     *
     * @param integer      $uid      用户id.
     * @param string|array $types    徽章类型.
     * @param boolean      $isFormat 是否格式化返回数据.
     *
     * @return array
     * @throws \Exception 异常信息
     */
    public function getInfoByTypes($uid, $types, $isFormat = true)
    {
        $res = array();
        if (!empty($types)) {
            $types = is_array($types) ? $types : explode(',', $types);
            $res = $this->getRedisInfo($uid, $types, $isFormat);
        }
        return $res;
    }

    /**
     * 根据多个徽章获取所有路径上徽章信息.
     *
     * @param integer      $uid      用户id.
     * @param string|array $types    徽章类型.
     * @param boolean      $isFormat 是否格式化返回数据.
     *
     * @return array
     * @throws \Exception 异常信息
     */
    public function getInfoFromUrls($uid, $types, $isFormat = true)
    {
        $res = array();
        if (!empty($types)) {
            $types = is_array($types) ? $types : explode(',', $types);
            $types = array_flip($types);
            $res = $this->getSubpathInfo($uid, $types, true, $isFormat);
        }
        return $res;
    }

    /**
     * 添加多个徽章信息(操作为加法).
     *
     * @param integer $uid  用户id.
     * @param array   $data 数据(array('type' => 1)).
     *
     * @return array
     * @throws \Exception 异常信息
     */
    public function addInfos($uid, array $data)
    {
        return empty($data) ? array() : $this->saveRedisInfo($uid, $data, 'add');
    }

    /**
     * 更新多个徽章信息(操作为减法).
     *
     * @param integer $uid  用户id.
     * @param array   $data 数据(array('type' => 1)).
     *
     * @return array
     * @throws \Exception 异常信息
     */
    public function updateInfos($uid, array $data)
    {
        return empty($data) ? array() : $this->saveRedisInfo($uid, $data, 'update');
    }

}
