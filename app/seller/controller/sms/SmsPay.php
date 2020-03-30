<?php

namespace app\seller\controller\sms;

use think\facade\Route;
use app\seller\controller\AuthController;
use crmeb\services\{
    sms\Sms, FormBuilder, JsonService, UtilService
};

/**
 * 短信购买
 * Class SmsPay
 * @package app\seller\controller\sms
 */
class SmsPay extends AuthController
{
    /**
     * @var Sms
     */
    protected $smsHandle;

    protected function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        $this->smsHandle = new Sms('yunxin', [
            'sms_account' => sys_config('sms_account'),
            'sms_token' => sys_config('sms_token'),
            'site_url' => sys_config('site_url')
        ]);
    }

    /**
     * 显示资源列表
     * @return string
     */
    public function index()
    {
        if (!$this->smsHandle->isLogin()) return redirect(url('sms.smsConfig/index').'?type=4&tab_id=18');
        return $this->fetch();
    }

    /**
     *  获取账号信息
     */
    public function number()
    {
        $countInfo = $this->smsHandle->count();
        if ($countInfo['status'] == 400) return JsonService::fail($countInfo['msg']);
        return JsonService::success($countInfo['data']);
    }

    /**
     *  获取支付套餐
     */
    public function price()
    {
        list($page, $limit) = UtilService::getMore([
            ['page', 1],
            ['limit', 20],
        ], null, true);
        $mealInfo = $this->smsHandle->meal($page, $limit);
        if ($mealInfo['status'] == 400) return JsonService::fail($mealInfo['msg']);
        return JsonService::success($mealInfo['data']['data']);
    }

    /**
     * 获取支付码
     */
    public function pay()
    {
        list($payType, $mealId, $price) = UtilService::postMore([
            ['payType', 'weixin'],
            ['mealId', 0],
            ['price', 0],
        ], null, true);
        $payInfo = $this->smsHandle->pay($payType, $mealId, $price, $this->adminId);
        if ($payInfo['status'] == 400) return JsonService::fail($payInfo['msg']);
        return JsonService::success($payInfo['data']);
    }

}