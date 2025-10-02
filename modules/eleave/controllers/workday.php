<?php
/**
 * @filesource modules/eleave/controllers/workday.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Workday;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=workday
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * @param Request $request
     * 
     * @return string
     */
    public function render(Request $request)
    {
        // ตรวจสอบสิทธิ์การเข้าถึง
        if ($login = Login::isMember()) {
            $params = array(
                'year_offset' => (int) Language::get('YEAR_OFFSET')
            );

            $params['year'] = $request->request('year')->toInt();
            $params['years'] = [];
            for ($i = (int)date('Y') - 1; $i <= (int)date('Y') + 1; $i++) {
                $params['years'][$i] = $i + $params['year_offset'];
            }
            $params['year'] = empty($params['year']) ? (int)date('Y') : $params['year'];

            // สร้าง Section
            $section = Html::create('section');
            // สร้าง div สำหรับเนื้อหา
            $div = $section->add('div', array(
                'class' => 'content_bg'
            ));
            // ตาราง (เรียกใช้ View)
            $div->appendChild(\Eleave\Workday\View::create()->render($request, $params));
            // คืนค่า HTML
            return $section->render();
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
