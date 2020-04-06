<?php
/**
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/11/13
 */

namespace app\seller\model\system;

use crmeb\traits\ModelTrait;
use crmeb\basic\BaseModel;
use think\facade\Session;

/**
 * 身份管理 model
 * Class SystemSellerRole
 * @package app\seller\model\system
 */
class SystemSellerRole extends BaseModel
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
    protected $name = 'system_seller_role';

    use ModelTrait;

    public static function setRulesAttr($value)
    {
        return is_array($value) ? implode(',',$value) : $value;
    }

    /**
     * 选择管理员身份
     * @param int $level
     * @return array
     */
    public static function getRole($where)
    {
        return self::where('status',1)->where($where)->column('role_name','id');
    }


    public static function rolesByAuth(string $rules)
    {
        if(empty($rules)) return [];
        $rules = self::where('id','IN',$rules)->where('status','1')->column('rules','id');
        $rules = array_unique(explode(',',implode(',',$rules)));
        $_auth = SystemSellerMenus::all(function($query) use($rules){
            $query->where('id','IN',$rules)
                ->where('controller|action','<>','')
                ->field('module,controller,action,params');
        });
        return self::tidyAuth($_auth?:[]);
    }

    public static function getAllAuth()
    {
        static $auth = null;
        $auth === null  && ($auth = self::tidyAuth(SystemSellerMenus::all(function($query){
            $query->where('controller|action','<>','')->field('module,controller,action,params');
        })?:[]));
        return $auth;
    }

    protected static function tidyAuth($_auth)
    {
        $auth = [];
        foreach ($_auth as $k=>$val){
            $auth[] =  SystemSellerMenus::getAuthName($val['action'],$val['controller'],$val['module'],$val['params']);
        }
        return $auth;
    }


    public static function systemPage($where){
        $model = new self;
        if(strlen(trim($where['role_name']))) $model = $model->where('role_name','LIKE',"%$where[role_name]%");
        if(strlen(trim($where['status']))) $model = $model->where('status',$where['status']);
        if(trim($where['level'])) $model = $model->where('seller_admin_id',$where['id']);
        return self::page($model,(function($item){
            $item->rules = SystemSellerMenus::where('id','IN',(string)$item->rules)->column('menu_name','id');
        }),$where);
    }


    public  function sellerAdmin(){
        return $this->hasmany('app\seller\model\system\SystemSellerAdmin','roles','id');
    }




}