<?php

/**
 * @filesource modules/eleave/views/workday.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

 namespace Eleave\Workday;

use Gcms\Login;
use Kotchasan\DataTable;
use Kotchasan\Http\Request;
use Kotchasan\Language;

class View extends \Gcms\View
{
    public function render(Request $request, $params)
    {
        $login = Login::isMember();
        $uri = $request->createUriWithGlobals(WEB_URL . 'index.php');

        // รับค่า month จาก request
        $params['month'] = $request->request('month')->filter('0-9');

        $params['id'] = $login['id'];
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => \Eleave\Workday\Model::toDataTable($params),
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('workday_perPage', 30)->toInt(),
            // เรียงลำดับ
            'sort' => ['month', 'year'],
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* ฟังก์ชั่นแสดงผล Footer */
            'onCreateFooter' => array($this, 'onCreateFooter'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('id', 'member_id'),
            /* คอลัมน์ที่สามารถค้นหาได้ */
            'searchColumns' => array('name', 'year'),
            /* ตัวเลือกการแสดงผลที่ส่วนหัว */
            'filters' => array(
                array(
                    'name' => 'year',
                    'text' => '{LNG_Year}',
                    'options' => $params['years'],
                    'value' => $params['year']
                ),
                array(
                    'name' => 'month',
                    'text' => '{LNG_Month}',
                    'options' => array(
                        '' => '{LNG_all items}',
                        '01' => '{LNG_Jan}',
                        '02' => '{LNG_Feb}',
                        '03' => '{LNG_Mar}',
                        '04' => '{LNG_Apr}',
                        '05' => '{LNG_May}',
                        '06' => '{LNG_Jun}',
                        '07' => '{LNG_Jul}',
                        '08' => '{LNG_Aug}',
                        '09' => '{LNG_Sep}',
                        '10' => '{LNG_Oct}',
                        '11' => '{LNG_Nov}',
                        '12' => '{LNG_Dec}'
                    ),
                    'value' => $params['month']
                )
            ),
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/eleave/model/workday/action',
            'actionCallback' => 'dataTableActionCallback',
            'headers' => array(
                'year' => array(
                    'text' => '{LNG_Year}',
                    'class' => 'center'
                ),
                'name' => array(
                    'text' => '{LNG_Name}',
                    'class' => 'left'
                ),
                'month' => array(
                    'text' => '{LNG_Month}',
                    'class' => 'center'
                ),
                'days' => array(
                    'text' => '{LNG_Workday}',
                    'class' => 'center'
                )
            ),
            'cols' => array(
                'year' => array(
                    'class' => 'center'
                ),
                'name' => array(
                    'class' => 'left'
                ),
                'month' => array(
                    'class' => 'center'
                ),
                'days' => array(
                    'class' => 'center'
                )
            ),
            'buttons' => array(
                'edit' => array(
                    'class' => 'icon-edit button green',
                    'href' => $uri->createBackUri(array('module' => 'eleave-editworkday', 'member_id' => ':member_id', 'year' => ':year', 'month' => ':month')),
                    'text' => '{LNG_Edit}'
                ),
                'delete' => array(
                    'class' => 'icon-delete button red',
                    'id' => ':id',
                    'text' => '{LNG_Delete}'
                )
            ),
            'addNew' => array(
                'class' => 'float_button icon-new',
                'href' => $uri->createBackUri(array('module' => 'eleave-editworkday', 'id' => 0))
            )
        ));

        // save cookie
        setcookie('workday_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);

        // สร้างตาราง
        $content = $table->render();
        if (trim($content) === '') {
            $content = '<div class="padding-left-right-bottom"><div class="center">{LNG_No data to display}</div></div>';
        }
        // คืนค่า HTML
        return $content;
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
         $item['days'] = \Eleave\Workday\Model::gettotaldays($item['member_id'], $item['year'], $item['month']);
         $item['year'] += 543;
         $month = Language::get('MONTH_SHORT');
         $item['month'] = $month[ltrim($item['month'], '0')];
         return $item;
     }
 }
 
