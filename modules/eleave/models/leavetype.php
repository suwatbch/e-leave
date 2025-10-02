<?php
/**
 * @filesource eleave/models/leavetype.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Leavetype;

use Kotchasan\Language;

/**
 * คลาสสำหรับอ่านประเภทการลา
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model
{

    /**
     * @var array
     */
    private $datas = [];
    /**
     * @var array
     */
    private $num_days = [];

    /**
     * อ่านรายชื่อการลา
     *
     * @return static
     */
    public static function init()
    {
        $obj = new static;
        // Query
        $query = \Kotchasan\Model::createQuery()
            ->select('id', 'topic', 'num_days')
            ->from('leave')
            ->where(array('published', 1))
            ->order('id')
            ->cacheOn();
        foreach ($query->execute() as $item) {
            $obj->datas[$item->id] = $item->topic;
            $obj->num_days[$item->id] = $item->num_days;
        }
        return $obj;
    }

    /**
     * ลิสต์รายชื่อการลา
     * สำหรับใส่ลงใน select
     *
     * @return array
     */
    public function toSelect()
    {
        // if (!empty($this->datas)) {
        //     $add = array(0 => "--".Language::get('Select leave')."--");
        //     foreach ($add as $key => $value){
        //         $this->datas = array($key => $value) + $this->datas;
        //     }
        // }
        return empty($this->datas) ? [] : $this->datas;
    }
    private static $dayAbbreviations = [
        'Monday'    => 'จ',
        'Tuesday'   => 'อ',
        'Wednesday' => 'พ',
        'Thursday'  => 'พฤ',
        'Friday'    => 'ศ',
        'Saturday'  => 'ส',
        'Sunday'    => 'อ'
    ];
    

    public static function getshifttype()

    {
        $obj = new static;
        $obj->datas = array(); // เริ่มต้นตัวแปร datas เป็นอาร์เรย์ว่าง
    
        // Query
        $query = \Kotchasan\Model::createQuery()
            ->select('id', 'description', 'workweek')
            ->from('shift')
            ->where(array('static', 1))
            ->order('id')
            ->cacheOn();
        
        foreach ($query->execute() as $item) {
            // แปลง workweek ให้เป็นตัวย่อ
            $workweek = json_decode($item->workweek, true);
            if (is_array($workweek)) {
                $workweek = array_map(function($day) {
                    return self::$dayAbbreviations[$day] ?? $day;
                }, $workweek);
            } else {
                $workweek = '';
            }
            
            // เก็บ description และ workweek ที่แปลงแล้วในอาร์เรย์ datas
            $obj->datas[$item->id] = [
                'description' => $item->description,
                'workweek' => implode(',', $workweek)
            ];
        }
        return $obj;
    }
    


 /**
 * ลิสต์รายชื่อการลา
 * สำหรับใส่ลงใน select
 *
 * @return array
 */
public function toshifttype()
{
    $options = array(); // สร้างอาเรย์เปล่าสำหรับเก็บข้อมูล dropdown

    if (!empty($this->datas)) {
        // เพิ่มตัวเลือกพิเศษ "กะหมุนเวียน"
        $options[0] = '{LNG_Rotating}';
        
        // วนลูปผ่าน $this->datas เพื่อสร้างตัวเลือก dropdown
        foreach ($this->datas as $id => $data) {
            // รวม description และ workweek เพื่อแสดงใน dropdown
            $options[$id] = $data['description'] . ' (' . $data['workweek'] . ')';
        }
    }

    return $options;
}



    /**
     * @param int $id
     * @return static
     */
    public static function getshift($id)
    {
        $obj = new static;
        // Query
        $query = \Kotchasan\Model::createQuery()
            ->select('id', 'description','workweek')
            ->from('shift')
            ->where(array('id', $id))
            ->cacheOn();
        foreach ($query->execute() as $item) {
            $obj->datas[$item->id] = $item->description;
        }
        return $obj;
    }

    /**
     * @return array
     */
    public function selectshift()
    {
        return $this->datas;
    }

    /**
     * @return static
     */
    public static function getshiftAll()
    {
        $obj = new static;
        // Query
        $query = \Kotchasan\Model::createQuery()
            ->select('id', 'description')
            ->from('shift')
            ->cacheOn();
        foreach ($query->execute() as $item) {
            $obj->datas[$item->id] = $item->description;
        }
        return $obj;
    }

    /**
     * @return array
     */
    public function selectshiftAll()
    {
        return $this->datas;
    }

    /**
     * อ่านรายชื่อการลาจาก $id
     * ไม่พบ คืนค่าว่าง
     *
     * @param int $id
     *
     * @return string
     */
    public function get($id)
    {
        return empty($this->datas[$id]) ? '' : $this->datas[$id];
    }

    /**
     * อ่านค่าจำนวนวันลาจาก $id
     * ไม่พบ คืนค่า null
     *
     * @param int $id
     *
     * @return int|null
     */
    public function numDays($id)
    {
        return isset($this->num_days[$id]) ? $this->num_days[$id] : null;
    }
}
