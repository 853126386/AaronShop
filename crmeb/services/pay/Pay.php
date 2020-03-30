<?php
/**
 * Created by PhpStorm.
 * User: xurongyao <763569752@qq.com>
 * Date: 2019/11/23 3:47 PM
 */

namespace crmeb\services\pay;

use crmeb\basic\BaseManager;
use crmeb\services\pay\storage\WechatPay;
use think\facade\Config;

/**
 * Class Pay
 * @package crmeb\services\auth
 * @mixin WechatPay
 */
class Pay extends BaseManager
{

    /**
     * 空间名
     * @var string
     */
    protected $namespace = '\\crmeb\\services\\pay\\storage\\';

    /**
     * 默认驱动
     * @return mixed
     */
    protected function getDefaultDriver()
    {
        return Config::get('pay.default', 'wechat');
    }
}