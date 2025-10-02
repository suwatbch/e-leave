<?php
/**
 * @filesource modules/eleave/controllers/editworkday.php
 */

namespace Eleave\Editworkday;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=editworkday
 */
class Controller extends \Gcms\Controller
{
    /**
     * ฟอร์มสร้าง/แก้ไข ประเภทการลา
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        $index = \Eleave\Editworkday\Model::get($request->request('id')->toInt());
        $params['member_id'] = $request->request('member_id')->toInt();
        $params['year'] = $request->request('year')->toInt();
        $params['month'] = $request->request('month')->toString();
        if (!empty($params['month'])) {
            $params['month'] = sprintf('%02d', $params['month']);
        }
        
        // ข้อความ title bar
        $title = ($params['member_id'] == 0 )? '{LNG_Add}' : '{LNG_Edit}';
        $this->title = Language::trans($title.'{LNG_Workday}');
        // เลือกเมนู
        $this->menu = 'eleave';
        // สามารถจัดการโมดูลได้
        if ($login = Login::isMember()) {
            $params['year_offset'] = (int) Language::get('YEAR_OFFSET');
            $params['year'] = $request->request('year')->toInt();
            $params['years'] = [];
            for ($i = (int)date('Y') - 1; $i <= (int)date('Y') + 1; $i++) {
                $params['years'][$i] = $i + $params['year_offset'];
            }
            $params['year'] = empty($params['year']) ? (int) date('Y') : $params['year'];
            $params['months'] = Language::get('MONTH_LONG');
            $params['month'] = empty($params['month']) ? date('m') : $params['month'];

            $index->member_id = $params['member_id'];
            $index->year = $params['year'];
            $index->month = $params['month'];

            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('nav', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-create">{LNG_Workday}</span></li>');
            $ul->appendChild('<li><span>'.$title.'</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-write">'.$this->title.'</h2>'
            ));
            $div = $section->add('div', array(
                'class' => 'content_bg'
            ));
            // แสดงฟอร์ม
            $div->appendChild(\Eleave\Editworkday\View::create()->render($index, $params));
            // คืนค่า HTML
            return $section->render();
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
