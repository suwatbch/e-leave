<?php

/**
 * @filesource modules/eleave/models/editworkday.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Editworkday;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=editworkday
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * @param int $id
     * @return array
     */
    public static function getUser($id)
    {
        $where = array(array('shift_id', 0));
        if ($id != 1) {
            $where[] = array(array('m1', $id));
        }
        return static::createQuery()
            ->select('id', 'name')
            ->from('user')
            ->where($where)
            ->execute();
    }

    /**
     * อ่านข้อมูลรายการที่เลือก
     * ถ้า $id = 0 หมายถึงรายการใหม่
     *
     * @param int   $id    ID
     *
     * @return object|null คืนค่าข้อมูล object ไม่พบคืนค่า null
     */
    public static function get($id)
    {
        if (empty($id)) {
            // ใหม่
            return (object) array(
                'id' => 0
            );
        } else {
            // แก้ไข อ่านรายการที่เลือก
            return static::createQuery()
                ->from('shift_workdays')
                ->where(array('id', $id))
                ->first();
        }
    }

    /**
     * @return array
     */
    public static function getShiftOptions()
    {
        $query = static::createQuery()
            ->select('id', 'name')
            ->from('shift')
            ->order('id ASC');

        $result = ['' => Null];
        foreach ($query->execute() as $item) {
            $result[$item->id] = $item->name;
        }

        return $result;
    }


    /**
     * @param object $index
     * @return array
     */
    public static function toDataTable($index)
    {
        $member_id = $index->member_id ?? 0;
        $year = $index->year ?? date('Y');
        $month = $index->month ?? date('m');

        $workdays = self::getWorkdaysForMember($member_id, $year, $month);
        $dayShifts = self::getDayShiftsFromWorkdays($workdays);
        return self::generateMonthlyShiftData($year, $month, $dayShifts);
    }

    /**
     * @param int $member_id
     * @param int $year
     * @param string $month
     * @return array
     */
    private static function getWorkdaysForMember($member_id, $year, $month)
    {
        return static::createQuery()
            ->select('*')
            ->from('shift_workdays')
            ->where([
                ['member_id', $member_id],
                ['year', $year],
                ['month', $month]
            ])
            ->execute();
    }

    /**
     * @param array $workdays
     * @return array
     */
    private static function getDayShiftsFromWorkdays($workdays)
    {
        $dayShifts = [];
        foreach ($workdays as $workday) {
            $days = json_decode($workday->days, true);
            foreach ($days as $day) {
                $dayShifts[$day] = $workday->shift_id;
            }
        }
        return $dayShifts;
    }

    /**
     * @param int $year
     * @param string $month
     * @param array $dayShifts
     * @return array
     */
    private static function generateMonthlyShiftData($year, $month, $dayShifts)
    {
        $result = [];
        $date = new \DateTime("{$year}-{$month}-01");
        $lastDay = (int) $date->format('t'); // Get the last day of the month

        for ($day = 1; $day <= $lastDay; $day++) {
            $currentDate = $date->format('Y-m-d');
            $shift_id = $dayShifts[$currentDate] ?? Null;
            $result[] = [
                'date' => self::convertToBuddhistEra($currentDate),
                'shift' => $shift_id
            ];
            $date->modify('+1 day');
        }
        return $result;
    }

    /**
     * @param string $date
     * @return string
     */
    private static function convertToBuddhistEra($date)
    {
        return \Gcms\Functions::convertDateToBuddhistEra($date);
    }

    public function submit(Request $request)
    {
        $ret = [];
        // ตรวจสอบการเริ่มต้น session, token, และการเข้าสู่ระบบ
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            if (Login::notDemoMode($login) && Login::checkPermission($login, 'can_approve_eleave')) {
                $useredit = $request->post('useredit')->toInt();
                $member_id = $request->post('member_id')->toInt();
                $member_id_hid = $request->post('member_id_hid')->toInt();
                $member_id = $member_id == 0 ? $member_id_hid : $member_id;
                $year = $request->post('year')->toInt();
                $year_hid = $request->post('year_hid')->toInt();
                $year = $year == 0 ? $year_hid : $year;
                $month = $request->post('month')->topic();
                $month_hid = $request->post('month_hid')->topic();
                $month = empty($month) ? sprintf('%02d', $month_hid) : sprintf('%02d', $month);
                $days = $request->post('days', [])->topic();
                $shifts = $request->post('shifts', [])->toInt();

                if (!empty($year) && !empty($month)) {
                    if ($member_id == 0) {
                        $ret['alert'] = Language::get('Unable to complete the transaction');
                    }
                    try {
                        $save = [];
                        $loop = 0;
                        foreach ($shifts as $key => $value) {
                            $found = false;
                            foreach ($save as &$svalue) {
                                if ($value === $svalue->shift_id) {
                                    $svalue->days = substr($svalue->days, 0, -2) . '","' . \Gcms\Functions::convertDateToYYYYMMDD($days[$key]) . '"]';
                                    $found = true;
                                    break;
                                }
                            }

                            if (!$found && $value > 0) {
                                $temp = [
                                    'member_id' => $member_id,
                                    'year' => $year,
                                    'month' => $month,
                                    'shift_id' => $value,
                                    'days' => '["' . \Gcms\Functions::convertDateToYYYYMMDD($days[$key]) . '"]'
                                ];
                                $save[$loop++] = (object)$temp;
                            }
                        }
                        if (empty($save) && $useredit == 0) {
                            $ret['alert'] = Language::get('Unable to complete the transaction');
                        }
                        if (empty($ret)) {
                            // db
                            $db = $this->db();
                            // เรียกข้อมูลเดิม
                            $workdaysOld = self::getWorkdaysForMember($member_id, $year, $month);
                            $workdaysOldTemp = $workdaysOld;
                            // วนทำงาน
                            foreach ($save as $items) {
                                $found = false;
                                $AddWorkdays = [];
                                foreach ($workdaysOld as &$old) {
                                    if ($items->shift_id == $old->shift_id) {
                                        $found = true;
                                        $AddWorkdays['days'] = $items->days;
                                        $db->update($this->getTableName('shift_workdays'), $old->id, $AddWorkdays);
                                        break;
                                    }
                                }

                                if (!$found) {
                                    $AddWorkdays['member_id'] = $items->member_id;
                                    $AddWorkdays['year'] = $items->year;
                                    $AddWorkdays['month'] = $items->month;
                                    $AddWorkdays['shift_id'] = $items->shift_id;
                                    if (is_array($items->days)) {
                                        // ถ้าเป็น array แปลงเป็น JSON string โดยใช้ json_encode
                                        $AddWorkdays['days'] = json_encode($items->days);
                                    } else {
                                        // ถ้าเป็น JSON string ให้ใช้โดยตรง
                                        $AddWorkdays['days'] = $items->days;
                                    }
                                    $db->insert($this->getTableName('shift_workdays'), $AddWorkdays);
                                }
                            }

                            // ลบข้อมูลที่ไม่มีใน $save แต่มีใน $workdaysOld
                            foreach ($workdaysOldTemp as $oldt) {
                                $found = false;
                                foreach ($save as $items) {
                                    if ($items->shift_id == $oldt->shift_id) {
                                        $found = true;
                                        break;
                                    }
                                }
                                if (!$found) {
                                    $db->delete($this->getTableName('shift_workdays'), array('id', $oldt->id), 0);
                                }
                            }
                            $ret['alert'] = Language::get('Saved successfully');
                            $ret['location'] = $request->getUri()->postBack('index.php', array('module' => 'workday'));
                            // เคลียร์
                            $request->removeToken();
                        }
                    } catch (\Kotchasan\InputItemException $e) {
                        $ret['alert'] = $e->getMessage();
                    }
                }
            } else {
                $ret['alert'] = Language::get('Permission denied');
            }
        } else {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }

        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }
}
