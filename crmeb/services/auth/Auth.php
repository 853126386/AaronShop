<?php
/**
 * Created by PhpStorm.
 * User: xurongyao <763569752@qq.com>
 * Date: 2019/11/23 3:47 PM
 */

namespace crmeb\services\auth;

use crmeb\basic\BaseManager;
use think\facade\Config;

class Auth extends BaseManager
{

    /**
     * 空间名
     * @var string
     */
    protected $namespace = '\\crmeb\\services\\auth\\storage\\';

    /**
     * 默认驱动
     * @return mixed
     */
    protected function getDefaultDriver()
    {
        return Config::get('auth.default', 'wechat');
    }

}