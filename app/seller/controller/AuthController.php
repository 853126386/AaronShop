<?php

namespace app\seller\controller;

use app\seller\model\system\SystemSeller;
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

    protected $storeId;

    /**
     * 当前管理员权限
     * @var array
     */
    protected $auth = [];

    protected $skipLogController = ['index', 'common'];

    protected function initialize()
    {
        parent::initialize();
        if (!SystemSeller::hasActiveAdmin()) return $this->redirect(Url::buildUrl('login/index')->suffix(false)->build());
        try {
            $adminInfo = SystemSeller::activeAdminInfoOrFail();
        } catch (\Exception $e) {
            return $this->failed(SystemSeller::getErrorInfo($e->getMessage()), Url::buildUrl('login/index')->suffix(false)->build());
        }
        $this->adminInfo = $adminInfo;
        $this->adminId = $adminInfo['id'];
        $this->storeId = $adminInfo['store_id'];
        $this->getActiveAdminInfo();
        $this->auth = SystemSeller::activeAdminAuthOrFail();
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
     * @return SystemSeller
     */
    protected function getActiveAdminInfo()
    {
        $adminId = $this->adminId;
        $adminInfo = SystemSeller::getValidAdminInfoOrFail($adminId);
        if (!$adminInfo) $this->failed(SystemSeller::getErrorInfo('请登陆!'));
        $this->adminInfo = $adminInfo;
        SystemSeller::setLoginInfo($adminInfo);
        return $adminInfo;
    }
}