<?php

namespace app\seller\controller\system;

use app\seller\controller\AuthController;
use app\seller\model\system\SystemSeller;
use app\seller\model\system\SystemLog as LogModel;
use crmeb\services\UtilService as Util;

/**
 * 管理员操作记录表控制器
 * Class SystemLog
 * @package app\seller\controller\system
 */
class SystemLog extends AuthController
{
    /**
     * 显示操作记录
     */
    public function index()
    {
        LogModel::deleteLog();
        $where = Util::getMore([
            ['pages', ''],
            ['path', ''],
            ['ip', ''],
            ['admin_id', ''],
            ['data', ''],
        ], $this->request);
        $where['level'] = $this->adminInfo['level'];
        $this->assign('where', $where);
        $this->assign('admin', SystemSeller::getOrdAdmin('id,real_name', $this->adminInfo['level']));
        $this->assign(LogModel::systemPage($where));
        return $this->fetch();
    }


}

