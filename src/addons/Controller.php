<?php
/**
 * User: alexzy<59254502@qq.com>
 * Date: 2020-04-24
 */

namespace think\addons;

use think\facade\Config;
use think\facade\View;

class Controller extends \think\Controller
{
    // 当前插件操作
    protected $addon = null;
    protected $controller = null;
    protected $action = null;
    // 当前template
    protected $template;

    /**
     * 无需登录的方法,同时也就不需要鉴权了
     * @var array
     */
    protected $noNeedLogin = ['*'];

    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = ['*'];

    /**
     * 权限Auth
     * @var Auth
     */
    protected $auth = null;

    /**
     * 布局模板
     * @var string
     */
    protected $layout = null;

    public function __construct()
    {
        Config::set('template.view_path', ADDON_PATH . request()->module() . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR);
        View::engine(Config::get('template.'));
        parent::__construct();

    }

}