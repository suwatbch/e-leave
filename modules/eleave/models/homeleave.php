<?php
/**
 * @filesource modules/eleave/models/homeleave.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Homeleave;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=eleave-homeleave
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Query ข้อมูลสำหรับส่งให้กับ DataTable
     *
     * @param array $params
     *
     * @return \Kotchasan\Database\QueryBuilder
     */
    public static function toDataTable($params)
    {
        $res = static::createQuery()
            ->select('F.id', 'F.create_date', 'U.name', 'F.leave_id', 'F.start_date',
                'F.days', 'F.start_time', 'F.end_time', 'F.times', 'F.start_period', 'F.end_date', 'F.end_period', 'F.member_id', 'F.communication', 'F.detail')
            ->from('leave_items F')
            ->join('user U', 'LEFT', array('U.id', 'F.member_id'))
            ->where(array(
                array('F.start_date', 'LIKE', $params['year'].'%'),
                array('F.member_id', $params['member_id'])
            ))
            ->order('F.start_date DESC');
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
            // id ที่ส่งมา
            if (preg_match_all('/,?([0-9]+),?/', $request->post('id')->toString(), $match)) {
                if ($action == 'detail') {
                    // แสดงรายละเอียดคำขอลา
                    $index = \Eleave\View\Model::get((int) $match[1][0]);
                    if ($index) {
                        $ret['modal'] = Language::trans(\Eleave\View\View::create()->render($index));
                    }
                } 
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }
}
