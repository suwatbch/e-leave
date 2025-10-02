<?php

/**
 * @filesource modules/index/views/shiftedit.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Shiftedit;

use Kotchasan\Html;
use Kotchasan\Language;

/**
 * module=shiftedit
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    public function render($index)
    {
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/index/model/shiftedit/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Shift details}'
        ));
        // shift name
        $fieldset->add('text', array(
            'id' => 'name',
            'label' => '{LNG_Shift id}<em>*</em>',
            'labelClass' => 'g-input icon-edit',
            'itemClass' => 'item',
            'maxlength' => 2,
            'value' => isset($index->name) ? $index->name : '',
            'required' => true
        ));
        // static
        $fieldset->add('select', array(
            'id' => 'static',
            'labelClass' => 'g-input icon-file',
            'label' => '{LNG_Static}<em>*</em>',
            'itemClass' => 'item',
            'options' => array(0 => '{LNG_Rotating}', 1 => '{LNG_Fixed}'),
            'value' => isset($index->static) ? $index->static : 0,
        ));
        // workweek
        $workweekDays = isset($index->workweek) ? json_decode($index->workweek, true) : array();
        $fieldset->add('checkboxgroups', array(
            'id' => 'workweek',
            'labelClass' => 'g-input icon-calendar',
            'itemClass' => 'item',
            'label' => '{LNG_Work days}',
            'options' => array(
                'Monday' => '{LNG_Monday}',
                'Tuesday' => '{LNG_Tuesday}',
                'Wednesday' => '{LNG_Wednesday}',
                'Thursday' => '{LNG_Thursday}',
                'Friday' => '{LNG_Friday}',
                'Saturday' => '{LNG_Saturday}',
                'Sunday' => '{LNG_Sunday}'
            ),
            'value' => $workweekDays,
        ));
        // // ประกาศฟังค์ชั่น genTimes
        $starttime = empty($index->start_time) ? '' : date("Y-m-d") . '' . $index->start_time;
        $times = \Gcms\Functions::genTimes($index->$starttime);
        // ตรวจสอบว่าเวลาเริ่มพักมากกว่าเวลาเลิกงานและเวลาเลิกพักน้อยกว่าเวลาเลิกงาน


        // กลุ่มสำหรับเวลาเริ่มและเวลาสิ้นสุด
        $work_time_group = $fieldset->add('groups');

        // เวลาเริ่ม
        $work_time_group->add('select', array(
            'id' => 'start_time',
            'labelClass' => 'g-input icon-clock',
            'itemClass' => 'width50',
            'label' => '{LNG_Start time}<em>*</em>',
            'options' => $times,
            'value' => isset($index->start_time) ? $index->start_time : '',
            'required' => true
        ));

        // เวลาสิ้นสุด
        $work_time_group->add('select', array(
            'id' => 'end_time',
            'labelClass' => 'g-input icon-clock',
            'itemClass' => 'width50',
            'label' => '{LNG_End time}<em>*</em>',
            'options' => $times,
            'value' => isset($index->end_time) ? $index->end_time : ' ',
            'required' => true
        ));

        // กลุ่มสำหรับเวลาเริ่มต้นพักและเวลาพักสิ้นสุด
        $break_time_group = $fieldset->add('groups');

        // เวลาเริ่มต้นพัก
        $break_time_group->add('select', array(
            'id' => 'start_break_time',
            'labelClass' => 'g-input icon-clock',
            'itemClass' => 'width50',
            'label' => '{LNG_Start break time}<em>*</em>',
            'options' => $times,
            'value' => isset($index->start_break_time) ? $index->start_break_time : '',
            'required' => true
        ));

        // เวลาพักสิ้นสุด
        $break_time_group->add('select', array(
            'id' => 'end_break_time',
            'labelClass' => 'g-input icon-clock',
            'itemClass' => 'width50',
            'label' => '{LNG_End break time}<em>*</em>',
            'options' => $times,
            'value' => isset($index->end_break_time) ? $index->end_break_time : '',
            'required' => true
        ));

        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button ok large icon-save',
            'value' => '{LNG_Save}'
        ));
        // description
        // $fieldset->add('hidden', array(
        //     'name' => 'description',
        //     'label' => '{LNG_Description}',
        //     'value' => $index->description
        // ));

        $fieldset->add('hidden', array(
            'id' => 'id',
            'name' => 'id',
            'value' => $index->id
        ));

        $fieldset->add('hidden', array(
            'id' => 'skipdate',
            'value' => ''
        ));

        // Javascript
        $form->script('initIndexShiftedit();');
        // คืนค่า HTML
        return $form->render();
    }
}
