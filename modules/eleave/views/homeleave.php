<?php
/**
 * @filesource modules/eleave/views/homeleave.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Homeleave;

use Kotchasan\DataTable;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=eleave-homeleave
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * @var object
     */
    private $leavetype;
    /**
     * @var array
     */
    private $leave_period;
    /**
     * @var int
     */
    private $days = 0;

    /**
     * แสดงรายการลา (แอดมิน)
     *
     * @param Request $request
     * @param array $params
     * @param array $login
     *
     * @return string
     */
    public function render(Request $request, $params, $login)
    {
        // Leave type
        $this->leavetype = \Eleave\Leavetype\Model::init();
        $this->leave_period = Language::get('LEAVE_PERIOD');
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'index.php');

        $years = array(
            $params['year'] => $params['year'] + $params['year_offset']
        );
        for ($y = ((int)$params['fiscal_year'] - 1); $y <= $params['fiscal_year'] + 1; $y++) {
            $years[$y] = $y + $params['year_offset'];
        }
        ksort($years);

        $buttons = array(
            'statistics' => array(
                'class' => 'icon-stats button pink',
                'href' => $uri->createBackUri(array('module' => 'eleave-statistics', 'id' => ':member_id', 'start_date' => ':start_date')),
                'text' => '{LNG_Statistics for leave}'
            ),
            'detail' => array(
                'class' => 'icon-info button orange',
                'id' => ':id',
                'text' => '{LNG_Detail}'
            ));

        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => \Eleave\Homeleave\Model::toDataTable($params),
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('eleaveHomeleave_perPage', 30)->toInt(),
            /* เรียงลำดับ */
            'sort' => $request->cookie('eleaveHomeleave_sort', 'start_date DESC')->toString(),
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* ฟังก์ชั่นแสดงผล Footer */
            'onCreateFooter' => array($this, 'onCreateFooter'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('id', 'start_period', 'end_date', 'end_time','end_period', 'member_id', 'times'),
            /* ตัวเลือกการแสดงผลที่ส่วนหัว */
            'filters' => array(
                array(
                    'name' => 'year',
                    'text' => '{LNG_Year}',
                    'options' => $years,
                    'value' => $params['year']
                )
            ),
            'action' => 'index.php/eleave/model/homeleave/action',
            'actionCallback' => 'dataTableActionCallback',
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'create_date' => array(
                    'text' => '{LNG_Transaction date}',
                    'sort' => 'create_date'
                ),
                'name' => array(
                    'text' => '{LNG_Name}',
                    'sort' => 'name'
                ),
                'leave_id' => array(
                    'text' => '{LNG_Leave type}',
                    'sort' => 'leave_id'
                ),
                'start_date' => array(
                    'text' => '{LNG_Date of leave}',
                    'sort' => 'start_date'
                ),
                'days' => array(
                    'text' => '{LNG_Date time}',
                    'class' => 'left'
                ),
                'start_time' => array(
                    'text' => '{LNG_Time}'
                ),
                'communication' => array(
                    'text' => '{LNG_Communication}'
                ),
                'detail' => array(
                    'text' => '{LNG_Detail}'
                )
            ),
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => array(
                'days' => array(
                    'class' => 'left'
                )
            ),
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => $buttons,
        ));
        // save cookie
        setcookie('eleaveHomeleave_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);
        setcookie('eleaveHomeleave_sort', $table->sort, time() + 2592000, '/', HOST, HTTPS, true);
        // คืนค่า HTML
        return $table->render();
    }

    /**
     * จัดรูปแบบการแสดงผลในแต่ละแถว
     *
     * @param array  $item ข้อมูลแถว
     * @param int    $o    ID ของข้อมูล
     * @param object $prop กำหนด properties ของ TR
     *
     * @return array
     */
    public function onRow($item, $o, $prop)
    {
        $this->days += $item['days'];
        $this->times += $item['times'];
        $item['create_date'] = Date::format($item['create_date'], 'd M Y');
        $item['leave_id'] = $this->leavetype->get($item['leave_id']);
        if ($item['start_date'] == $item['end_date']) {
            $item['start_date'] = Date::format($item['start_date'], 'd M Y').' '.$this->leave_period[$item['start_period']];
        } else {
            $item['start_date'] = Date::format($item['start_date'], 'd M Y').' '.$this->leave_period[$item['start_period']].($item['start_period'] ? '' : ' - '.Date::format($item['end_date'], 'd M Y').' '.$this->leave_period[$item['end_period']]);
        }
        $item['days'] = \Gcms\Functions::gettimeleave($item['days'],$item['times']);
        $item['start_time'] = \Gcms\Functions::showtime($item['start_time'],$item['end_time']);
        return $item;
    }

    /**
     * ฟังก์ชั่นสร้างแถวของ footer
     * 
     * @return string
     */
    public function onCreateFooter()
    {
        return '<tr><td></td><td class=right colspan=3>{LNG_Total}</td><td class=left>'.\Gcms\Functions::getttotalleave($this->days,$this->times).'</td><td colspan="2"></td></tr>';
    }
}
