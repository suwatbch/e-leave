<?php

namespace Index\Shifts;

use Kotchasan\DataTable;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=shifts
 */
class View extends \Gcms\View
{
    /**
     * @var array
     */
    private $publisheds;

    /**
     * จัดการกะการทำงาน
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        $this->publisheds = Language::get('PUBLISHEDS');
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL . 'index.php');
        // ตาราง
        $table = new DataTable(array(
            'uri' => $uri,
            'model' => \Index\Shifts\Model::toDataTable(),
            'perPage' => $request->cookie('Shifts_perPage', 30)->toInt(),
            'sort' => 'id ASC',
            'onRow' => array($this, 'onRow'),
            'hideColumns' => array('id'),
            'action' => 'index.php/index/model/shifts/action',
            'actionCallback' => 'dataTableActionCallback',
            'searchColumns' => array('name', 'Static'),
            'headers' => array(
                'name' => array(
                    'text' => '{LNG_Shift id}',
                    'class' => 'center'
                ),
                'static' => array(
                    'text' => '{LNG_Static}',
                    'class' => 'center'
                ),
                'start_time' => array(
                    'text' => '{LNG_Start time}',
                    'class' => 'center'
                ),
                'end_time' => array(
                    'text' => '{LNG_End time}',
                    'class' => 'center'
                ),
                'start_break_time' => array(
                    'text' => '{LNG_Start break time}',
                    'class' => 'center'
                ),
                'end_break_time' => array(
                    'text' => '{LNG_End break time}',
                    'class' => 'center'
                ),
            ),
            'cols' => array(
                'name' => array('class' => 'center'),
                'static' => array('class' => 'center'),
                'start_time' => array('class' => 'center'),
                'end_time' => array('class' => 'center'),
                'start_break_time' => array('class' => 'center'),
                'end_break_time' => array('class' => 'center')
            ),
            'buttons' => array(
                'edit' => array(
                    'class' => 'icon-edit button green',
                    'href' => $uri->createBackUri(array('module' => 'shiftedit', 'id' => ':id')),
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
                'href' => $uri->createBackUri(array('module' => 'shiftedit')),
                'title' => '{LNG_Add} {LNG_Manage shift}'
            )
        ));

        // save cookie
        setcookie('Shifts_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);

        // สร้างตาราง
        $content = $table->render();
        // คืนค่า HTML
        return $content;
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
        $item['sequence'] = $o + 1;
        $item['static'] = $item['static'] == 1 ? '{LNG_Fixed}' : '{LNG_Rotating}';
        $item['published'] = '<a id="published_' . $item['id'] . '" class="icon-published' . $item['published'] . '" title="' . $this->publisheds[$item['published']] . '"></a>';
        return $item;
    }
}
