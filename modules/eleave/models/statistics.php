<?php
/**
 * @filesource modules/eleave/models/statistics.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Statistics;

use Kotchasan\Database\Sql;

/**
 * module=eleave-statistics
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * สรุปประวัติการลารายบุคคล
     *
     * @param array $params
     *
     * @return array
     */
    public static function execute($params)
    {
        $where = array(
            array('F.leave_id', 'I.id'),
            array('F.member_id', $params['member_id']),
            array('F.status', 1),
            array('I.published', 1)
        );
        if (!empty($params['from'])) {
            $where[] = array('F.start_date', '>=', $params['from']);
        }
        if (!empty($params['to'])) {
            $where[] = array('F.start_date', '<=', $params['to']);
        }
        return static::createQuery()
            ->select('I.id' ,'I.topic', 'I.num_days', Sql::SUM('days', 'days'), Sql::SUM('times', 'times'))
            ->from('leave I')
            ->join('leave_items F', 'LEFT', $where)
            ->groupBy('I.topic')
            ->cacheOn()
            ->execute();
    }

    /**
     * สรุปประวัติการลารายบุคคล
     *
     * @param array $params
     *
     * @return array
     */
    public static function executeQuota($params)
    {
        $where = array(
            array('I.year', $params['year']),
            array('I.year', NULL)
        );
        return static::createQuery()
            ->select('I.*')
            ->from('leave_quota I')
            ->where(array('I.member_id', $params['member_id']))
            ->andWhere($where, 'OR')
            ->cacheOn()
            ->execute();
    }
}
