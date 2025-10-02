<?php

/**
 * @filesource modules/index/views/holidays.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Holidays;

use Kotchasan\DataTable;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Date;

/**
 * module=holidays
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * @var array
     */
    private $publisheds;

    /**
     * รายการวันหยุด
     *
     * @param Request $request
     * @param array $params
     * @return string
     */
    public function render(Request $request, $params)
    {
        $this->publisheds = Language::get('PUBLISHEDS');
        $uri = $request->createUriWithGlobals(WEB_URL . 'index.php');

        $year = $request->request('year')->toInt();
        if ($year == 0) {
            $addNew = array(
                'class' => 'float_button icon-new',
                'href' => $uri->createBackUri(array('module' => 'editholidays', 'id' => ':id'))
            );
        } else {
            $addNew = array(
                'class' => 'float_button icon-new',
                'href' => $uri->createBackUri(array('module' => 'editholidays', 'id' => 0))
            );
        }
        
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => \Index\Holidays\Model::toDataTable($params),
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('holidays_perPage', 30)->toInt(),
            // เรียงลำดับ
            'sort' => 'holidays',
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* ฟังก์ชั่นแสดงผล Footer */
            'onCreateFooter' => array($this, 'onCreateFooter'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('id'),
            /* คอลัมน์ที่สามารถค้นหาได้ */
            'searchColumns' => array('holidays', 'description'),
            /* ตัวเลือกการแสดงผลที่ส่วนหัว */
            'filters' => array(
                array(
                    'name' => 'year',
                    'text' => '{LNG_Year}',
                    'options' => $params['years'],
                    'value' =>  $params['year']
                )
            ),
            'action' => 'index.php/index/model/holidays/action',
            'actionCallback' => 'dataTableActionCallback',
            'headers' => array(
                'holidays' => array(
                    'text' => '{LNG_Date}',
                    'class' => 'center'
                ),
                'description' => array(
                    'text' => '{LNG_Description}',
                    'class' => 'left'
                ),
                'published' => array(
                    'text' => ''
                )
            ),
            'cols' => array(
                'holidays' => array(
                    'class' => 'center'
                ),
                'description' => array(
                    'class' => 'left'
                ),
                'published' => array(
                    'class' => 'center'
                )
            ),
            'buttons' => array(
                'edit' => array(
                    'class' => 'icon-edit button green',
                    'href' => $uri->createBackUri(array('module' => 'editholidays', 'id' => ':id')),
                    'text' => '{LNG_Edit}'
                ),
                'delete' => array(
                    'class' => 'icon-delete button red',
                    'id' => ':id',
                    'text' => '{LNG_Delete}'
                )
            ),
            'addNew' => $addNew,
        ));
        // save cookie
        setcookie('holidays_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);
        // คืนค่า HTML
        return $table->render();
    }


    /**
     * จัดรูปแบบการแสดงผลในแต่ละแถว.
     *
     * @param array $item
     *
     * @return array
     */
    public function onRow($item, $o, $prop)
    {
        $item['holidays'] = Date::format($item['holidays'], 'd M Y');
        $item['num_days'] = $item['num_days'] == 0 ? '{LNG_Unlimited}' : $item['num_days'];
        $item['published'] = '<a id=published_' . $item['id'] . ' class="icon-published' . $item['published'] . '" title="' . $this->publisheds[$item['published']] . '"></a>';
        return $item;
    }
}
