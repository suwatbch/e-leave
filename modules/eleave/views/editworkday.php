<?php

/**
 * @filesource modules/eleave/views/editworkday.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Editworkday;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\DataTable;
use Kotchasan\Form;
use Kotchasan\Language;

/**
 * module=editworkday
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * @param static $index
     * @param array $params
     * @return
     */
    public function render($index,$params)
    {
        // รายการที่ต้องการ
        $login = Login::isMember();
        $data = \Eleave\Editworkday\Model::toDataTable($index);
        $sumshift = 0;
        foreach ($data as $item){
            if (!empty($item['shift'])) {
                $sumshift += 1;
            }
        }
        $disabled = false;
        $useredit = 0;
        if ($sumshift > 0 && $index->member_id > 0) {
            $disabled = true;
            $useredit = 1;
        }
        $allusertemp = \Eleave\Editworkday\Model::getUser($login['id']);
        $add = array(0 => "-- {LNG_Select employee} --");
        $alluser = $add;
        foreach ($allusertemp as $key => $value) {
            $alluser[$value->id] = $value->name;
        }
        // form
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/eleave/model/editworkday/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Details of}{LNG_Workday}'
        ));
        $fieldset->add('hidden', array(
            'id' => 'id',
            'value' => $index->id
        ));
        $fieldset->add('hidden', array(
            'id' => 'useredit',
            'value' => $useredit
        ));
        $groups = $fieldset->add('groups');
        // username
        $groups->add('select', array(
            'id' => 'member_id',
            'labelClass' => 'g-input icon-menus',
            'itemClass' => 'width33',
            'label' => '{LNG_Employee}<em>*</em>',
            'options' => $alluser,
            'disabled' => $disabled,
            'value' => $index->member_id
        ));
        $fieldset->add('hidden', array(
            'id' => 'member_id_hid',
            'value' => $index->member_id
        ));
        // year
        $groups->add('select', array(
            'id' => 'year',
            'labelClass' => 'g-input icon-clock',
            'itemClass' => 'width33',
            'label' => '{LNG_Year}<em>*</em>',
            'options' => $params['years'],
            'disabled' => $disabled,
            'value' => $index->year
        ));
        $fieldset->add('hidden', array(
            'id' => 'year_hid',
            'value' => $index->year
        ));
        // month
        $groups->add('select', array(
            'id' => 'month',
            'labelClass' => 'g-input icon-calendar',
            'itemClass' => 'width33',
            'label' => '{LNG_Month}<em>*</em>',
            'options' => $params['months'],
            'disabled' => $disabled,
            'value' => (int) $index->month
        ));
        $fieldset->add('hidden', array(
            'id' => 'month_hid',
            'value' => (int) $index->month
        ));
        // ตาราง
        $table = new DataTable(array(
            /* ข้อมูลใส่ลงในตาราง */
            'datas' => $data,
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* กำหนดให้ input ตัวแรก (id) รับค่าเป็นตัวเลขเท่านั้น */
            'border' => true,
            // 'pmButton' => true,
            'responsive' => true,
            'showCaption' => false,
            'headers' => array(
                'date' => array(
                    'text' => '{LNG_Date}'
                ),
                'shift' => array(
                    'text' => '{LNG_Shift work}'
                )
            )
        ));
        $fieldset->add('div', array(
            'class' => 'item',
            'innerHTML' => $table->render()
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        $groups = $fieldset->add('groups-table');
        // submit
        $groups->add('submit', array(
            'class' => 'button ok large icon-save',
            'value' => '{LNG_Save}'
        ));
        // Javascript
        $form->script('initSelectChange("module=workday&module=eleave-editworkday", ["member_id", "year", "month"]);');
        // คืนค่า HTML
        return $form->render();
    }

    public function onRow($item, $o, $prop)
    {
        $shiftOptions = \Eleave\Editworkday\Model::getShiftOptions();
        $item['date'] = Form::text(array(
            'name' => 'days[]',
            'labelClass' => 'g-input icon-calendar',
            'value' => $item['date'],
            'readonly' => true
        ))->render();
        $item['shift'] = Form::select(array(
            'name' => 'shifts[]',
            'labelClass' => 'g-input icon-edit',
            'options' => $shiftOptions,
            'value' => $item['shift']
        ))->render();
        return $item;
    }
}
