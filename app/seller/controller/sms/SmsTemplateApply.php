<?php

namespace app\seller\controller\sms;

use app\seller\controller\AuthController;
use crmeb\services\{
    FormBuilder, JsonService, sms\Sms, UtilService
};
use think\facade\Route;

/**
 * 短信模板申请
 * Class SmsTemplateApply
 * @package app\seller\controller\sms
 */
class SmsTemplateApply extends AuthController
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
     *
     * @return string
     */
    public function index()
    {
        if (!$this->smsHandle->isLogin()) return redirect(url('sms.smsConfig/index').'?type=4&tab_id=18');
        $sms_account = sys_config('sms_account');
        $this->assign('sms_account',$sms_account);
        return $this->fetch();
    }

    /**
     * 异步获取模板列表
     */
    public function lst()
    {
        $where = UtilService::getMore([
            ['status', ''],
            ['title', ''],
            ['page', 1],
            ['limit', 20],
        ]);
        $templateList = $this->smsHandle->template($where);
        if ($templateList['status'] == 400) return JsonService::fail($templateList['msg']);
        return JsonService::successlayui($templateList['data']);
    }

    /**
     * 显示创建资源表单页.
     *
     * @return string
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function create()
    {
        $field = [
            FormBuilder::input('title', '模板名称'),
            FormBuilder::textarea('text', '模板内容示例', '【您的短信签名】您的验证码是：{$code}，有效期为{$time}分钟。如非本人操作，可不用理会。模板中的{$code}和{$time}需要替换成对应的变量，请开发者知晓。修改此项无效！'),
            FormBuilder::input('content', '模板内容')->type('textarea'),
            FormBuilder::radio('type', '模板类型', 1)->options([['label' => '验证码', 'value' => 1], ['label' => '通知', 'value' => 2], ['label' => '推广', 'value' => 3]])
        ];
        $form = FormBuilder::make_post_form('申请短信模板', $field, Route::buildUrl('save'), 2);
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }

    /**
     * 保存新建的资源
     */
    public function save()
    {
        $data = UtilService::postMore([
            ['title', ''],
            ['content', ''],
            ['type', 0]
        ]);
        if (!strlen(trim($data['title']))) return JsonService::fail('请输入模板名称');
        if (!strlen(trim($data['content']))) return JsonService::fail('请输入模板内容');
        $applyStatus = $this->smsHandle->apply($data['title'], $data['content'], $data['type']);
        if ($applyStatus['status'] == 400) return JsonService::fail($applyStatus['msg']);
        return JsonService::success('申请成功');
    }
}