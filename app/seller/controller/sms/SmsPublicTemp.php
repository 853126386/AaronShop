<?php

namespace app\seller\controller\sms;

use app\seller\controller\AuthController;
use crmeb\services\{
    JsonService, sms\Sms, UtilService
};

/**
 * 公共短信模板
 * Class SmsPublicTemp
 * @package app\seller\controller\sms
 */
class SmsPublicTemp extends AuthController
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

    public function index()
    {
        if (!$this->smsHandle->isLogin()) return $this->failed('请先填写短信配置');
        return $this->fetch();
    }

    /**
     * 异步获取公共模板列表
     */
    public function lst()
    {
        $where = UtilService::getMore([
            ['is_have', ''],
            ['page', 1],
            ['limit', 20],
        ]);
        $templateList = $this->smsHandle->publictemp($where);
        if ($templateList['status'] == 400) return JsonService::fail($templateList['msg']);
        return JsonService::successlayui($templateList['data']);
    }

    /**
     * 添加公共短信模板
     */
    public function status()
    {
        list($id, $tempId) = UtilService::postMore([
            ['id', 0],
            ['tempId', 0]
        ], null, true);
        if (!(int)$id) return JsonService::fail('参数错误');
        if (!strlen(trim($tempId))) return JsonService::fail('参数错误');
        $useStatus = $this->smsHandle->use($id, $tempId);
        if ($useStatus['status'] == 400) return JsonService::fail($useStatus['msg']);
        return JsonService::success($useStatus['msg']);
    }


}