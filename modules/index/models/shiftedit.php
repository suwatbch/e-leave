<?php
/**
 * @filesource modules/index/models/shiftedit.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Shiftedit;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=shiftedit
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
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
                'id' => 0,
                'name' => '',
                'static' => '',
                'start_time' => '',
                'end_time' => '',
                'start_break_time' => '',
                'end_break_time' => '',
                'skipdate' => 0,
                'description' => '',
            );
        } else {
            // แก้ไข อ่านรายการที่เลือก
            return static::createQuery()
                ->from('shift')
                ->where(array('id', $id))
                ->first();
        }
    }

    /**
     * บันทึกข้อมูลที่ส่งมาจากฟอร์ม (shiftedit.php)
     *
     * @param Request $request
     */

    public function submit(Request $request)
    {
        $ret = [];
        // ตรวจสอบ session, token, สิทธิ์การใช้งาน
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            if (Login::notDemoMode($login) && Login::checkPermission($login, 'can_manage_eleave')) {
                try {
                    // รับค่าและตรวจสอบข้อมูล
                    $save = array(
                        'name' => $request->post('name')->topic(),
                        'static' => $request->post('static')->toInt(),
                        'start_time' => $request->post('start_time')->topic(),
                        'end_time' => $request->post('end_time')->topic(),
                        'start_break_time' => $request->post('start_break_time')->topic(),
                        'end_break_time' => $request->post('end_break_time')->topic(),
                        'workweek' => $request->post('workweek')->topic(),
                        'description' => $request->post('description')->topic(),
                        'skipdate' => $request->post('skipdate')->toInt(),
                    );
                    $description = sprintf(
                        '%s %s - %s พัก %s - %s',
                        $request->post('name')->topic(),
                        date('H:i', strtotime($request->post('start_time')->topic())),
                        date('H:i', strtotime($request->post('end_time')->topic())),
                        date('H:i', strtotime($request->post('start_break_time')->topic())),
                        date('H:i', strtotime($request->post('end_break_time')->topic()))
                    );
                    // เพิ่มค่า description ลงในข้อมูลที่จะบันทึก
                    $save['description'] = $description;

                    $startTime = strtotime($save['start_time']);
                    $endTime = strtotime($save['end_time']);
                    $startBreakTime = strtotime($save['start_break_time']);
                    $endBreakTime = strtotime($save['end_break_time']);

                    if ($startTime === false || $endTime === false || $startBreakTime === false || $endBreakTime === false) {
                        throw new \Kotchasan\InputItemException('The entered time format is invalid.');
                    }
    
                    // ตรวจสอบว่ากะข้ามวันหรือไม่
                    $isCrossDay = $startTime > $endTime;
    
                    if ($isCrossDay) {
                        // หากกะข้ามวัน ให้ปรับเวลาสิ้นสุดเป็นวันถัดไป
                        $endTime += 24 * 3600;
                    }

                    // ปรับเวลาพักถ้าข้ามวัน
                    if ($startBreakTime <= $startTime) {
                        $startBreakTime += $isCrossDay ? 24 * 3600 : 0;
                    }
                    if ($endBreakTime <= $startTime) {
                        $endBreakTime += $isCrossDay ? 24 * 3600 : 0;
                    }
                    if (empty($request->post('start_time'))) {
                        throw new \Kotchasan\InputItemException(Language::replace('Please select a %s.', [Language::get('start time')]));
                    }
                    if (empty($request->post('end_time'))) {
                        throw new \Kotchasan\InputItemException('Please choose a time off work.');
                    }
                    if (empty($request->post('start_break_time'))) {
                        throw new \Kotchasan\InputItemException(Language::replace('Please select a %s.', [Language::get('start break time')]));
                    }
                    if (empty($request->post('end_break_time'))) {
                        throw new \Kotchasan\InputItemException('Please select the break end time.');
                    }
                    // เวลาเลิกพักต้องมากกว่าเวลาเริ่มพัก
                    if ($endBreakTime <= $startBreakTime) {
                        throw new \Kotchasan\InputItemException(Language::get('End break time must be greater than start break time.'));
                    }                    
                    // เวลาเริ่มพักต้องมากกว่าเวลาเริ่มงาน
                    if ($startBreakTime <= $startTime) {
                        throw new \Kotchasan\InputItemException(Language::get('Break start time must be greater than work start time.'));
                    }
                    // เวลาเลิกพักต้องน้อยกว่าเวลาเลิกงาน
                    // if ($endBreakTime >= $endTime) {
                    //     throw new \Kotchasan\InputItemException(Language::get('The time off for rest must be less than the time off work.'));
                    // }
                    $minimumHours = 8 * 3600; // 8 ชั่วโมง = 8 * 60 นาที * 60 วินาที = 28,800 วินาที
                    $workDuration = $endTime - $startTime; // คำนวณระยะเวลาการทำงานเป็นวินาที

                    if ($workDuration < $minimumHours) {
                        throw new \Kotchasan\InputItemException(Language::get('The time from start to finish must not be less than 8 hours.'));
                    }
                    // ตรวจสอบว่าระยะเวลาเริ่มพักถึงเลิกพักห้ามเกิน 1 ชั่วโมง
                    $breakDuration = $endBreakTime - $startBreakTime; // คำนวณระยะเวลาพักเป็นวินาที
                    if ($breakDuration > 3600) { // 3600 วินาที = 1 ชั่วโมง
                        throw new \Kotchasan\InputItemException(Language::get('The rest period must not exceed 1 hour.'));
                    }

                    // รับข้อมูล workweek และแปลงเป็น JSON เพื่อบันทึก
                    if ($request->post('static')->toInt() == 1) {
                        $workweek = $request->post('workweek', [])->toArray(); // รับ array ของวันทำงาน
                        $save['workweek'] = !empty($workweek) ? json_encode($workweek) : null;
                    } else {
                        $save['workweek'] = null; // ถ้าเป็นกะหมุนเวียน ไม่บันทึก workweek
                    }

                    // บันทึกค่าลงในฟิลด์ skipdate
                    $save['skipdate'] = $isCrossDay ? 1 : 0;

                    // บันทึกข้อมูลอื่นๆ
                    $id = $request->post('id')->toInt();
                    if ($id == 0) {
                        // เพิ่มใหม่
                        $id = $this->db()->insert($this->getTableName('shift'), $save);
                    } else {
                        // แก้ไข
                        $this->db()->update($this->getTableName('shift'), $id, $save);
                    }

                    // log
                    \Index\Log\Model::add($id, 'index', 'Save', '{LNG_Shift} ID : ' . $id, $login['id']);

                    // ส่งค่ากลับ
                    $ret['alert'] = Language::get('Saved successfully');
                    $ret['location'] = $request->getUri()->postBack('index.php', array('module' => 'shifts'));
                    // เคลียร์
                    $request->removeToken();

                } catch (\Kotchasan\InputItemException $e) {
                    $ret['alert'] = $e->getMessage();
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // ส่งค่ากลับเป็น JSON
        echo json_encode($ret);
    }
}