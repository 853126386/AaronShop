<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/12/18
 */

namespace app\models\store;

use app\admin\model\ump\StoreSeckillTime;
use crmeb\basic\BaseModel;
use crmeb\services\GroupDataService;
use app\admin\model\store\StoreProductAttrValue;
use app\models\store\StoreProduct;

/**
 * TODO 秒杀产品Model
 * Class StoreSeckill
 * @package app\models\store
 */
class StoreSeckill extends BaseModel
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
    protected $name = 'store_seckill';

    protected function getImagesAttr($value)
    {
        return json_decode($value, true) ?: [];
    }

    public function getDescriptionAttr($value)
    {
        return htmlspecialchars_decode($value);
    }

    public static function getSeckillCount()
    {
        $seckillTime = sys_data('routine_seckill_time') ?: [];//秒杀时间段
        $timeInfo = ['time' => 0, 'continued' => 0];
        foreach ($seckillTime as $key => $value) {
            $currentHour = date('H');
            $activityEndHour = bcadd((int)$value['time'], (int)$value['continued'], 0);
            if ($currentHour >= (int)$value['time'] && $currentHour < $activityEndHour && $activityEndHour < 24) {
                $timeInfo = $value;
                break;
            }
        }
        if ($timeInfo['time'] == 0) return 0;
        $activityEndHour = bcadd((int)$timeInfo['time'], (int)$timeInfo['continued'], 0);
        $startTime = bcadd(strtotime(date('Y-m-d')), bcmul($timeInfo['time'], 3600, 0));
        $stopTime = bcadd(strtotime(date('Y-m-d')), bcmul($activityEndHour, 3600, 0));
        return self::where('is_del', 0)->where('status', 1)->where('start_time', '<=', $startTime)->where('stop_time', '>=', $stopTime)->count();
    }

    /**
     * 获取秒杀列表
     * @param $time
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function seckillList($time, $page = 0, $limit = 20)
    {
        if ($page) $list = StoreSeckill::where('is_del', 0)->where('status', 1)->where('start_time', '<=', time())->where('stop_time', '>=', time() - 86400)->where('time_id', $time)->order('sort desc')->page($page, $limit)->select();
        else $list = StoreSeckill::where('is_del', 0)->where('status', 1)->where('start_time', '<=', time())->where('stop_time', '>=', time() - 86400)->where('time_id', $time)->order('sort desc')->select();
        if ($list) return $list->hidden(['cost', 'add_time', 'is_del'])->toArray();
        return [];
    }

    /**
     * 获取所有秒杀产品
     * @param string $field
     * @return array
     */
    public static function getListAll($offset = 0, $limit = 10, $field = 'id,product_id,image,title,price,ot_price,start_time,stop_time,stock,sales')
    {
        $time = time();
        $model = self::where('is_del', 0)->where('status', 1)->where('stock', '>', 0)->field($field)
            ->where('start_time', '<', $time)->where('stop_time', '>', $time)->order('sort DESC,add_time DESC');
        $model = $model->limit($offset, $limit);
        $list = $model->select();
        if ($list) return $list->toArray();
        else return [];
    }

    /**
     * 获取热门推荐的秒杀产品
     * @param int $limit
     * @param string $field
     * @return array
     */
    public static function getHotList($limit = 0, $field = 'id,product_id,image,title,price,ot_price,start_time,stop_time,stock')
    {
        $time = time();
        $model = self::where('is_hot', 1)->where('is_del', 0)->where('status', 1)->where('stock', '>', 0)->field($field)
            ->where('start_time', '<', $time)->where('stop_time', '>', $time)->order('sort DESC,add_time DESC');
        if ($limit) $model->limit($limit);
        $list = $model->select();
        if ($list) return $list->toArray();
        else return [];
    }

    /**
     * 获取一条秒杀产品
     * @param $id
     * @param string $field
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public static function getValidProduct($id, $field = '*')
    {
        $time = time();
        $info = self::alias('n')->join('store_product c', 'c.id=n.product_id')->where('n.id', $id)->where('n.is_del', 0)->where('n.status', 1)->where('n.start_time', '<', $time)->where('n.stop_time', '>', $time)->field('n.*,SUM(c.sales+c.ficti) as total')->find();
        return $info;
    }

    /**
     * 获取秒杀是否有开启
     * @return bool
     */
    public static function getSeckillContStatus()
    {
        $time = time();
        $count = self::where('is_del', 0)->where('status', 1)->where('start_time', '<', $time)->where('stop_time', '>', $time)->count();
        return $count ? true : false;
    }

    public static function initFailSeckill()
    {
        self::where('is_hot', 1)->where('is_del', 0)->where('status', '<>', 1)->where('stop_time', '<', time())->update(['status' => '-1']);
    }

    public static function idBySimilaritySeckill($id, $limit = 4, $field = '*')
    {
        $time = time();
        $list = [];
        $productId = self::where('id', $id)->value('product_id');
        if ($productId) {
            $list = array_merge($list, self::where('product_id', $productId)->where('id', '<>', $id)
                ->where('is_del', 0)->where('status', 1)->where('stock', '>', 0)
                ->field($field)->where('start_time', '<', $time)->where('stop_time', '>', $time)
                ->order('sort DESC,add_time DESC')->limit($limit)->select()->toArray());
        }
        $limit = $limit - count($list);
        if ($limit) {
            $list = array_merge($list, self::getHotList($limit, $field));
        }

        return $list;
    }

    /** 获取秒杀产品库存
     * @param $id
     * @return mixed
     */
    public static function getProductStock($id)
    {
        return self::where('id', $id)->value('stock');
    }

    /**
     * 获取字段值
     * @param $id
     * @param string $field
     * @return mixed
     */
    public static function getProductField($id, $field = 'title')
    {
        return self::where('id', $id)->value($field);
    }

    /**
     * 修改秒杀库存
     * @param int $num
     * @param int $seckillId
     * @return bool
     */
    public static function decSeckillStock($num = 0, $seckillId = 0, $unique = '')
    {
        $product_id = self::where('id', $seckillId)->value('product_id');
        if ($unique) {
            $res = false !== StoreProductAttrValue::decProductAttrStock($seckillId, $unique, $num, 1);
            $res = $res && self::where('id', $seckillId)->dec('stock', $num)->inc('sales', $num)->update();
            $sku = StoreProductAttrValue::where('product_id', $seckillId)->where('unique', $unique)->where('type', 1)->value('suk');
            $res = $res && StoreProductAttrValue::where('product_id', $product_id)->where('suk', $sku)->where('type', 0)->dec('stock', $num)->inc('sales', $num)->update();
        } else {
            $res = false !== self::where('id', $seckillId)->dec('stock', $num)->inc('sales', $num)->update();
        }
        $res = $res && StoreProduct::where('id', $product_id)->dec('stock', $num)->inc('sales', $num)->update();
        return $res;
    }

    /**
     * 增加库存较少销量
     * @param int $num
     * @param int $seckillId
     * @return bool
     */
    public static function incSeckillStock($num = 0, $seckillId = 0)
    {
        $seckill = self::where('id', $seckillId)->field(['stock', 'sales'])->find();
        if (!$seckill) return true;
        if ($seckill->sales > 0) $seckill->sales = bcsub($seckill->sales, $num, 0);
        if ($seckill->sales < 0) $seckill->sales = 0;
        $seckill->stock = bcadd($seckill->stock, $num, 0);
        return $seckill->save();
    }
}