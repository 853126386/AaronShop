<?php

namespace app\seller\controller\setting;

use app\seller\controller\AuthController;
use app\seller\model\system\{SystemSellerMenus,SystemSeller, SystemSellerRole as RoleModel};
use crmeb\services\{UtilService as Util, JsonService as Json};
use think\facade\Route as Url;
/**
 * 身份管理  控制器
 * Class SystemSellerRole
 * @package app\seller\controller\setting
 */
class SystemRole extends AuthController
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
//        dd(SystemSeller::find($this->adminId)->sellerRole->store_id);
        $where = Util::getMore([
            ['status', ''],
            ['role_name', ''],
        ], $this->request);
        $where['level'] = $this->adminInfo['level'];
        $where['id'] = $this->adminInfo['id'];
        $where['store_id'] = $this->storeId;
        $this->assign('where', $where);
//        dump(RoleModel::systemPage($where));exit;
        $this->assign(RoleModel::systemPage($where));
        return $this->fetch();
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {

//        if(0 == 0){
//////        }else{
//////            dump($this->adminInfo['level']);
//////        }
        $menus = $this->adminInfo['level'] == 0 ? SystemSellerMenus::ruleList() : SystemSellerMenus::rolesByRuleList($this->adminInfo['roles']);
        $this->assign(['menus' => json($menus)->getContent(), 'saveUrl' => Url::buildUrl('save')]);
        return $this->fetch();
    }

    /**
     * 保存新建的资源
     *
     * @param \think\Request $request
     * @return \think\Response
     */
    public function save()
    {
        $data = Util::postMore([
            'role_name',
            ['status', 0],
            ['checked_menus', [], '', 'rules']
        ]);
        if (!$data['role_name']) return Json::fail('请输入身份名称');

        if (!is_array($data['rules']) || !count($data['rules']))
            return Json::fail('请选择最少一个权限');
        foreach ($data['rules'] as $v) {
            $pid = SystemSellerMenus::where('id', $v)->value('pid');
            if (!in_array($pid, $data['rules'])) $data['rules'][] = $pid;
        }

        $data['rules'] = implode(',', $data['rules']);
        $data['level'] = $this->adminInfo['level'] + 1;
        $data['store_id']=$this->storeId;

       if($this->adminInfo['level']>1){
            //限制商户管理员下的子用户添加角色
            return Json::fail('该身份不允许添加权限！');
        }


        if (!RoleModel::create($data)) return Json::fail('添加身份失败!');
        return Json::successful('添加身份成功!');
    }

    /**
     * 显示指定的资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function read($id)
    {
        //
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        //
        $role = RoleModel::get($id);
        $menus = $this->adminInfo['level'] == 0 ? SystemSellerMenus::ruleList() : SystemSellerMenus::rolesByRuleList($this->adminInfo['roles']);
        $this->assign(['role' => $role->toJson(), 'menus' => json($menus)->getContent(), 'updateUrl' => Url::buildUrl('update', array('id' => $id))]);
        return $this->fetch();
    }

    /**
     * 保存更新的资源
     *
     * @param \think\Request $request
     * @param int $id
     * @return \think\Response
     */
    public function update($id)
    {
        $data = Util::postMore([
            'role_name',
            ['status', 0],
            ['checked_menus', [], '', 'rules']
        ]);
        if (!$data['role_name']) return Json::fail('请输入身份名称');
        if (!is_array($data['rules']) || !count($data['rules']))
            return Json::fail('请选择最少一个权限');
        foreach ($data['rules'] as $v) {
            $pid = SystemSellerMenus::where('id', $v)->value('pid');
            if (!in_array($pid, $data['rules'])) $data['rules'][] = $pid;
        }
        $data['rules'] = implode(',', $data['rules']);
        if (!RoleModel::edit($data, $id)) return Json::fail('修改失败!');
        return Json::successful('修改成功!');
    }

    /**
     * 删除指定资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        if (!RoleModel::del($id))
            return Json::fail(RoleModel::getErrorInfo('删除失败,请稍候再试!'));
        else
            return Json::successful('删除成功!');
    }
}
