<?php
/**
 * @filesource modules/index/models/shifts.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Shifts;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=shifts
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
     * @return \Kotchasan\Database\QueryBuilder
     */
    public static function toDataTable()
    {
        return static::createQuery()
            ->select('id','name','static','start_time','end_time','start_break_time','end_break_time')
            ->from('shift');
    }

    /**
     * รับค่าจาก action (shiftedit.php)
     *
     * @param Request $request
     */
    public function action(Request $request)
    {
        $ret = [];
        // ตรวจสอบการเริ่มต้นเซสชัน, ที่มาของการร้องขอ, สิทธิ์ในการจัดการโมดูล, และไม่ใช่โหมดสาธิต
        if ($request->initSession() && $request->isReferer() && $login = Login::isMember()) {
            if (Login::notDemoMode($login) && Login::checkPermission($login, 'can_manage_eleave')) {
                // รับค่าจากการ POST
                $action = $request->post('action')->toString();
                // กำหนดชื่อตาราง
                $shiftTable = $this->getTableName('shift');
                $workdayTable = $this->getTableName('shift_workdays'); // สมมติว่าชื่อตารางคือ workday
    
                // รับ id ที่ส่งมา
                if (preg_match_all('/,?([0-9]+),?/', $request->post('id')->toString(), $match)) {
                    if ($action === 'delete') {
                        // ลบข้อมูลจากตาราง shift
                        $this->db()->delete($shiftTable, array('id', $match[1]), 0);
                        
                        // ลบข้อมูลที่เกี่ยวข้องจากตาราง workday
                        $this->db()->delete($workdayTable, array('shift_id', $match[1]), 0); // สมมติว่าตาราง workday มีคอลัมน์ shift_id
    
                        // บันทึกล็อก
                        \Index\Log\Model::add(0, 'eleave', 'Delete', '{LNG_Delete} {LNG_Shift} id : '.implode(', ', $match[1]), $login['id']);
                        // รีเฟรชหน้า
                        $ret['location'] = 'reload';
                    } elseif ($action === 'published') {
                        // การจัดการสถานะการเผยแพร่
                        $search = $this->db()->first($shiftTable, (int) $match[1][0]);
                        if ($search) {
                            $published = $search->published == 1 ? 0 : 1;
                            $this->db()->update($shiftTable, $search->id, array('published' => $published));
                            // คืนค่า
                            $ret['elem'] = 'published_'.$search->id;
                            $ret['title'] = Language::get('PUBLISHEDS', '', $published);
                            $ret['class'] = 'icon-published'.$published;
                            // บันทึกล็อก
                            \Index\Log\Model::add(0, 'eleave', 'Save', $ret['title'].' id : '.$match[1][0], $login['id']);
                        } 
                    }
                } elseif ($action == 'add') {
                    // รับค่าจากการ POST สำหรับการเพิ่ม
                    $name = $request->post('name')->toString();
                    $start_time = $request->post('start_time')->toString();
                    $end_time = $request->post('end_time')->toString();
                    $start_break_time = $request->post('start_break_time')->toString();
                    $end_break_time = $request->post('end_break_time')->toString();
                    
                    // ตรวจสอบการซ้ำกันของชื่อกะ
                    $data = $request->post('name')->toString();
    
                    // ตรวจสอบว่ามีกะที่มีเวลาเหมือนกันในฐานข้อมูลหรือไม่
                    $existingShift = $this->db()->createQuery()
                        ->from($shiftTable)
                        ->where(array(
                            array('start_time', $start_time),
                            array('end_time', $end_time)
                        ))
                        ->first();
    
                    if ($existingShift) {
                        $ret['alert'] = Language::get('This shift name already exists.');
                    } else {
                        // เพิ่มข้อมูลใหม่
                        $this->db()->insert($shiftTable, array(
                            'name' => $name,
                            'start_time' => $start_time,
                            'end_time' => $end_time,
                            'start_break_time' => $start_break_time,
                            'end_break_time' => $end_break_time,
                            'static' => 0 // หรือ 1 ขึ้นอยู่กับประเภทของกะ
                        ));
                        // บันทึกล็อก
                        \Index\Log\Model::add(0, 'eleave', 'Add', 'Add new shift on '.$name, $login['id']);
                        // คืนค่า
                        $ret['alert'] = Language::get('Added successfully');
                        $ret['location'] = 'reload';
                    }
                }
        }  
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }
    
}}