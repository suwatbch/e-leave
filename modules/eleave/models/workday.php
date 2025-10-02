<?php

/**
 * @filesource modules/eleave/models/workday.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

 namespace Eleave\Workday;

 use Gcms\Login;
 use Kotchasan\Http\Request;
 use Kotchasan\Language;
 
 class Model extends \Kotchasan\Model
 {
     public static function toDataTable($params)
     {
         $where = array(
             array('S.year', $params['year'])
         );
         
         if ($params['id'] != 1) {
             $where[] = array('U.m1', $params['id']);
         }
         
         if (!empty($params['month'])) {
             $where[] = array('S.month', $params['month']);
         }
     
         $query = static::createQuery()
             ->select('S.id', 'S.member_id', 'S.year', 'U.name', 'S.month', 'S.days')
             ->from('shift_workdays S')
             ->join('user U', 'LEFT', array('U.id', 'S.member_id'))
             ->where($where)
             ->groupBy('member_id', 'name', 'year', 'month')
             ->order('year DESC', 'month DESC');
     
         return $query;
     }

    /**
     * @param int $member_id
     * @param int $year
     * @param string $month
     * @return string
     */
    public static function gettotaldays($member_id, $year, $month)
    {
        $res = '';
        $workdays = static::createQuery()
            ->select('id', 'shift_id', 'days')
            ->from('shift_workdays')
            ->where(array(
                array('member_id', $member_id),
                array('year', $year),
                array('month', $month)
            ))
            ->cacheOn()
            ->execute();

        $days = [];
        foreach ($workdays as $workday) {
            $days[] = (object) ['days' => $workday->days];
        }
        $newdate = \Gcms\Functions::datanap($days, 'days');
        $res = count($newdate) . ' {LNG_days}';

        return $res;
    }

    /**
     * รับค่าจาก action
     *
     * @param Request $request
     */
    public function action(Request $request)
    {
        $ret = [];
        // session, referer, member
        if ($request->initSession() && $request->isReferer() && $login = Login::isMember()) {
            // ค่าที่ส่งมา
            $action = $request->post('action')->toString();
            $id = $request->post('id')->toString();
            if ($action == 'delete' && $id > 0 && Login::checkPermission($login, 'can_approve_eleave')) {

                $workdaysMaster = $this->createQuery()
                    ->from('shift_workdays')
                    ->where(array('id', $id))
                    ->cacheOn()
                    ->first('*');

                $workdays = $this->createQuery()
                    ->select('id')
                    ->from('shift_workdays')
                    ->where(array(
                        array('member_id', $workdaysMaster->member_id),
                        array('year', $workdaysMaster->year),
                        array('month', $workdaysMaster->month)
                    ))
                    ->cacheOn()
                    ->execute();

                foreach ($workdays as $items) {
                    // ลบ database
                    $this->db()->delete($this->getTableName('shift_workdays'), array('id', $items->id), 0);
                    // log
                    \Index\Log\Model::add(0, 'eleave', 'Delete', '{LNG_Delete} {LNG_Workday} ID : ' . implode(', ', $items->id), $login['id']);
                }
                // reload
                $ret['location'] = 'reload';
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }
}
