<?php

namespace app\seller\controller;

use app\seller\model\system\SystemSellerAdmin;
use app\seller\model\system\SystemSellerMenus;
use app\seller\model\system\SystemSellerRole;
use think\facade\Route as Url;

/**
 * 基类 所有控制器继承的类
 * Class AuthController
 * @package app\seller\controller
 */
class AuthController extends SystemBasic
{
    /**
     * 当前登陆管理员信息
     * @var
     */
    protected $adminInfo;

    /**
     * 当前登陆管理员ID
     * @var
     */
    protected $adminId;

    protected $sellerAdminId;

    /**
     * 当前管理员权限
     * @var array
     */
    protected $auth = [];

    protected $skipLogController = ['index', 'common'];

    protected function initialize()
    {
        parent::initialize();
        if (!SystemSellerAdmin::hasActiveAdmin()) return $this->redirect(Url::buildUrl('login/index')->suffix(false)->build());
        try {
            $adminInfo = SystemSellerAdmin::activeAdminInfoOrFail();
        } catch (\Exception $e) {
            return $this->failed(SystemSellerAdmin::getErrorInfo($e->getMessage()), Url::buildUrl('login/index')->suffix(false)->build());
        }
        $this->adminInfo = $adminInfo;
        $this->adminId = $adminInfo['id'];
        $this->sellerAdminId = $adminInfo['seller_admin_id'];
        $this->getActiveAdminInfo();
        $this->auth = SystemSellerAdmin::activeAdminAuthOrFail();
        $this->adminInfo->level === 0 || $this->checkAuth();
        $this->assign('_admin', $this->adminInfo);
        $type = 'system';
        event('AdminVisit', [$this->adminInfo, $type]);
    }


    protected function checkAuth($action = null, $controller = null, $module = null, array $route = [])
    {
        static $allAuth = null;
        if ($allAuth === null) $allAuth = SystemSellerRole::getAllAuth();
        if ($module === null) $module = app('http')->getName();
        if ($controller === null) $controller = $this->request->controller();
        if ($action === null) $action = $this->request->action();
        if (!count($route)) $route = $this->request->route();
        if (in_array(strtolower($controller), $this->skipLogController, true)) return true;
        $nowAuthName = SystemSellerMenus::getAuthName($action, $controller, $module, $route);
        $baseNowAuthName = SystemSellerMenus::getAuthName($action, $controller, $module, []);
        if ((in_array($nowAuthName, $allAuth) && !in_array($nowAuthName, $this->auth)) || (in_array($baseNowAuthName, $allAuth) && !in_array($baseNowAuthName, $this->auth)))
            exit($this->failed('没有权限访问!'));
        return true;
    }


    /**
     * 获得当前用户最新信息
     * @return SystemSellerAdmin
     */
    protected function getActiveAdminInfo()
    {
        $adminId = $this->adminId;
        $adminInfo = SystemSellerAdmin::getValidAdminInfoOrFail($adminId);
        if (!$adminInfo) $this->failed(SystemSellerAdmin::getErrorInfo('请登陆!'));
        $this->adminInfo = $adminInfo;
        SystemSellerAdmin::setLoginInfo($adminInfo);
        return $adminInfo;
    }
}