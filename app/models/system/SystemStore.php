<?php


namespace app\models\system;

use crmeb\traits\ModelTrait;
use crmeb\basic\BaseModel;

/**
 * 门店自提 model
 * Class SystemStore
 * @package app\model\system
 */
class SystemStore extends BaseModel
{
    const EARTH_RADIUS = 6371;

    use ModelTrait;

    /**
     * 数据表主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'system_store';


    public static function getLatlngAttr($value, $data)
    {
        return $data['latitude'] . ',' . $data['longitude'];
    }

    public static function verificWhere()
    {
        return self::where('is_show', 1)->where('is_del', 0);
    }

    /*
     * 获取门店信息
     * @param int $id
     * */
    public static function getStoreDispose($id = 0, $felid = '')
    {
        if ($id)
            $storeInfo = self::verificWhere()->where('id', $id)->find();
        else
            $storeInfo = self::verificWhere()->find();
        if ($storeInfo) {
            $storeInfo['latlng'] = self::getLatlngAttr(null, $storeInfo);
            $storeInfo['valid_time'] = $storeInfo['valid_time'] ? explode(' - ', $storeInfo['valid_time']) : [];
            $storeInfo['_valid_time'] = str_replace('-', '/', ($storeInfo['valid_time'][0] ?? '') . ' ~ ' . ($storeInfo['valid_time'][1] ?? ""));
            $storeInfo['day_time'] = $storeInfo['day_time'] ? str_replace(' - ', ' ~ ', $storeInfo['day_time']) : [];
            $storeInfo['_detailed_address'] = $storeInfo['address'] . ' ' . $storeInfo['detailed_address'];
            $storeInfo['address'] = $storeInfo['address'] ? explode(',', $storeInfo['address']) : [];
            if ($felid) return $storeInfo[$felid] ?? '';
        }
        return $storeInfo;
    }

    /**
     * 门店列表
     * @return mixed
     */
//    public static function lst()
//    {
//        $model = new self;
//        $model = $model->where('is_show', 1);
//        $model = $model->where('is_del', 0);
//        $model = $model->order('id DESC');
//        return $model->select();
//    }

    /**
     * 计算某个经纬度的周围某段距离的正方形的四个点
     *
     * @param lng float 经度
     * @param lat float 纬度
     * @param distance float 该点所在圆的半径，该圆与此正方形内切，默认值为2.5千米
     * @return array 正方形的四个点的经纬度坐标
     */
    public static function returnSquarePoint($lng, $lat, $distance = 200)
    {
        $dlng = 2 * asin(sin($distance / (2 * self::EARTH_RADIUS)) / cos(deg2rad($lat)));
        $dlng = rad2deg($dlng);
        $dlat = rad2deg($distance / self::EARTH_RADIUS);
        return [
            'left_top' => [
                'lat' => $lat + $dlat,
                'lng' => $lng - $dlng,
            ],
            'right_top' => [
                'lat' => $lat + $dlat,
                'lng' => $lng + $dlng
            ],
            'left_bottom' => [
                'lat' => $lat - $dlat,
                'lng' => $lng - $dlng
            ],
            'right_bottom' => [
                'lat' => $lat - $dlat,
                'lng' => $lng + $dlng
            ]
        ];
    }

    /*
     设置where条件
    */
    public static function nearbyWhere($model = null, $latitude = 0, $longitude = 0)
    {
        if (!is_object($model)) {
            $latitude = $model;
            $model = new self();
            $longitude = $latitude;
        }
        $field = "(round(6367000 * 2 * asin(sqrt(pow(sin(((latitude * pi()) / 180 - ({$latitude} * pi()) / 180) / 2), 2) + cos(({$latitude} * pi()) / 180) * cos((latitude * pi()) / 180) * pow(sin(((longitude * pi()) / 180 - ({$longitude} * pi()) / 180) / 2), 2))))) AS distance";
        $model->field($field);
        return $model;
    }

    /**
     * 获取排序sql
     * @param $latitude
     * @param $longitude
     * @return mixed
     */
    public static function distanceSql($latitude, $longitude)
    {
        $field = "(round(6367000 * 2 * asin(sqrt(pow(sin(((latitude * pi()) / 180 - ({$latitude} * pi()) / 180) / 2), 2) + cos(({$latitude} * pi()) / 180) * cos((latitude * pi()) / 180) * pow(sin(((longitude * pi()) / 180 - ({$longitude} * pi()) / 180) / 2), 2))))) AS distance";
        return $field;
    }

    /**
     * 门店列表
     * @return mixed
     */
    public static function lst($latitude, $longitude, $page, $limit)
    {
        $model = new self();
        $model = $model->where('is_del', 0);
        $model = $model->where('is_show',1);
        if ($latitude && $longitude) {
            $model = $model->field(['*', self::distanceSql($latitude, $longitude)])->order('distance asc');
        }
        $list = $model->page((int)$page, (int)$limit)
            ->select()
            ->hidden(['is_show', 'is_del'])
            ->toArray();
        if ($latitude && $longitude) {
            foreach ($list as &$value) {
                //计算距离
                $value['distance'] = sqrt((pow((($latitude - $value['latitude']) * 111000), 2)) + (pow((($longitude - $value['longitude']) * 111000), 2)));
                //转换单位
                $value['range'] = bcdiv($value['distance'], 1000, 1);
            }
//            $distanceKey = array_column($list, 'distance');
//            array_multisort($distanceKey, SORT_ASC, $list);
        }
        return $list;
    }

}