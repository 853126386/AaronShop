<?php
/**
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/11/11
 */

namespace app\seller\model\system;

use crmeb\traits\ModelTrait;
use crmeb\basic\BaseModel;
use think\facade\Session;

/**
 * Class SystemSeller
 * @package app\seller\model\system
 */
class SystemSeller extends BaseModel
{
    /**
     * 数据表主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'system_seller';

    use ModelTrait;

    protected $insert = ['add_time'];

    public static function setAddTimeAttr($value)
    {
        return time();
    }

    public static function setRolesAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }


    /**
     * 用户登陆
     * @param $account
     * @param $pwd
     * @return bool
     */
    public static function login($account, $pwd)
    {
//        $adminInfo = self::get(compact('account'));
        $adminInfo = self::where(compact('account'))->alias('sa')->join('eb_system_seller_role sr','sr.id=sa.roles')->field(['sa.*,sr.seller_admin_id'])->find();

        //当用户是商户管理员的时候，设置seller_admin_id为自己的id
        if($adminInfo['level']==1&&$adminInfo['seller_admin_id']==0)$adminInfo['seller_admin_id']=$adminInfo['id'];
        if (!$adminInfo) return self::setErrorInfo('登陆的账号不存在!');
        if ($adminInfo['pwd'] != md5($pwd)) return self::setErrorInfo('账号或密码错误，请重新输入');
        if (!$adminInfo['status']) return self::setErrorInfo('该账号已被关闭!');
        self::setLoginInfo($adminInfo);
        event('SystemAdminLoginAfter', [$adminInfo]);
        return true;
    }

    /**
     *  保存当前登陆用户信息
     */
    public static function setLoginInfo($adminInfo)
    {
        Session::set('sellerId', $adminInfo['id']);
        Session::set('sellerInfo', $adminInfo->toArray());
        Session::save();
    }

    /**
     * 清空当前登陆用户信息
     */
    public static function clearLoginInfo()
    {
        Session::delete('sellerInfo');
        Session::delete('sellerId');
        Session::save();
    }

    /**
     * 检查用户登陆状态
     * @return bool
     */
    public static function hasActiveAdmin()
    {
        return Session::has('sellerId') && Session::has('sellerInfo');
    }

    /**
     * 获得登陆用户信息
     * @return mixed
     * @throws \Exception
     */
    public static function activeAdminInfoOrFail()
    {
        $adminInfo = Session::get('sellerInfo');
        if (!$adminInfo) exception('请登陆');
        if (!$adminInfo['status']) exception('该账号已被关闭!');
        return $adminInfo;
    }

    /**
     * 获得登陆用户Id 如果没有直接抛出错误
     * @return mixed
     * @throws \Exception
     */
    public static function activeAdminIdOrFail()
    {
        $adminId = Session::get('sellerId');
        if (!$adminId) exception('访问用户为登陆登陆!');
        return $adminId;
    }

    /**
     * @return array|null
        * @throws \Exception
        */
    public static function activeAdminAuthOrFail()
    {
        $adminInfo = self::activeAdminInfoOrFail();
        if (is_object($adminInfo)) $adminInfo = $adminInfo->toArray();
        return $adminInfo['level'] === 0 ? SystemSellerRole::getAllAuth() : SystemSellerRole::rolesByAuth($adminInfo['roles']);
    }

    /**
     * 获得有效管理员信息
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public static function getValidAdminInfoOrFail($id)
    {
//        $adminInfo = self::get($id);
        $adminInfo =  self::where(['sa.id'=>$id])->alias('sa')->join('eb_system_seller_role sr','sr.id=sa.roles')->field(['sa.*,sr.seller_admin_id'])->find();
        if($adminInfo['level']==1&&$adminInfo['seller_admin_id']==0)$adminInfo['seller_admin_id']=$adminInfo['id'];
        if (!$adminInfo) exception('用户不能存在!');
        if (!$adminInfo['status']) exception('该账号已被关闭!');
        return $adminInfo;
    }

    /**
     * @param string $field
     * @param int $level
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getOrdAdmin($field = 'real_name,id', $level = 0)
    {
        return self::where('level', '>=', $level)->field($field)->select();
    }

    public static function getTopAdmin($field = 'real_name,id')
    {
        return self::where('level', 0)->field($field)->select();
    }

    /**
     * @param $where
     * @return array
     */
    public static function systemPage($where)
    {
        $model = new self;
        if ($where['name'] != '') $model = $model->where('account|real_name', 'LIKE', "%$where[name]%");
        if ($where['roles'] != '') $model = $model->where("CONCAT(',',roles,',')  LIKE '%,$where[roles],%'");
        if ($where['seller_admin_id'] !=0)$model =$model->where(['seller_admin_id'=>$where['seller_admin_id']]);
        $model = $model->alias('sa')->join('eb_system_seller_role sr','sa.roles=sr.id')->where('sa.level', $where['level'])->where('is_del', 0)->field('sa.*');
//        $model = $model->where('level', $where['level'])->where('is_del', 0);
        return self::page($model, function ($admin) {
            $admin->roles = SystemSellerRole::where('id', 'IN', [$admin->roles])->column('role_name');
        }, $where);
    }
}