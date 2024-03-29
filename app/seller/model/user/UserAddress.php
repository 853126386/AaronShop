<?php
/**
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/12/25
 */

namespace app\seller\model\user;

use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;

class UserAddress extends BaseModel
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
    protected $name = 'user_address';

    use ModelTrait;

    protected $insert = ['add_time'];

    protected function setAddTimeAttr()
    {
        return time();
    }

    public static function setDefaultAddress($id,$uid)
    {
        self::beginTrans();
        $res1 = self::where('uid',$uid)->update(['is_default'=>0]);
        $res2 = self::where('id',$id)->where('uid',$uid)->update(['is_default'=>1]);
        $res =$res1 !== false && $res2 !== false;
        self::checkTrans($res);
        return $res;
    }

    public static function userValidAddressWhere($model=null,$prefix = '')
    {
        if($prefix) $prefix .='.';
        $model = self::getSelfModel($model);
        return $model->where("{$prefix}is_del",0);
    }

    public static function getUserValidAddressList($uid,$field = '*')
    {
        return self::userValidAddressWhere()->where('uid',$uid)->order('add_time DESC')->field($field)->select()->toArray()?:[];
    }

    public static function getUserDefaultAddress($uid,$field = '*')
    {
        return self::userValidAddressWhere()->where('uid',$uid)->where('is_default',1)->field($field)->find();
    }
}