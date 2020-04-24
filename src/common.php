<?php
/**
 * User: alexzy<59254502@qq.com>
 * Date: 2020-04-24
 */

use think\Loader;
use think\facade\Hook;
use think\facade\Route;
use think\facade\App;
use think\facade\Cache;
use think\facade\Config;

// 插件目录
define('ADDON_PATH', \think\facade\Env::get('root_path') . 'addons' . DIRECTORY_SEPARATOR);

// 定义路由
Route::any('addons/:addon/[:controller]/[:action]', "\\think\\addons\\Route@execute");

// 如果插件目录不存在则创建
if (!is_dir(ADDON_PATH)) {
    @mkdir(ADDON_PATH, 0755, true);
}

// 监听addon_init
Hook::listen('addon_init');

// 闭包自动识别插件目录配置
Hook::add('app_init', function () {
    // 获取开关
    $autoload = (bool)Config::get('addons.autoload', false);
    // 非正是返回
    if (!$autoload) {
        return;
    }
    // 当debug时不缓存配置
    $config = App::$debug ? [] : Cache::get('addons', []);
    if (empty($config)) {
        $config = get_addon_autoload_config();
        Cache::set('addons', $config);
    }
});

//闭包初始化
Hook::add('app_init',function (){
    //注册路由
    $routeArr = (array)Config::get('addons.route');
    $domains = [];
    $rules = [];
    $execute = "\\think\\addons\\Route@execute?addon=%s&controller=%s&action=%s";
//    foreach ($routeArr as $k => $v) {
//        if (is_array($v)) {
//            $addon = $v['addon'];
//            $domain = $v['domain'];
//            $drules = [];
//            foreach ($v['rule'] as $m => $n) {
//                list($addon, $controller, $action) = explode('/', $n);
//                $drules[$m] = sprintf($execute . '&indomain=1', $addon, $controller, $action);
//            }
//            //$domains[$domain] = $drules ? $drules : "\\addons\\{$k}\\controller";
//            $domains[$domain] = $drules ? $drules : [];
//            $domains[$domain][':controller/[:action]'] = sprintf($execute . '&indomain=1', $addon, ":controller", ":action");
//        } else {
//            if (!$v)
//                continue;
//            list($addon, $controller, $action) = explode('/', $v);
//            $rules[$k] = sprintf($execute, $addon, $controller, $action);
//        }
//    }
//    Route::rule($rules);
//    if ($domains) {
//        Route::domain($domains);
//    }

    // 获取系统配置
    $hooks = App::isDebug() ? [] : Cache::get('hooks', []);
    if (empty($hooks)) {
        $hooks = (array)Config::get('addons.hooks');
        // 初始化钩子
        foreach ($hooks as $key => $values) {
            if (is_string($values)) {
                $values = explode(',', $values);
            } else {
                $values = (array)$values;
            }
            $hooks[$key] = array_filter(array_map('get_addon_class', $values));
        }
        Cache::set('hooks', $hooks);
    }
    //如果在插件中有定义app_init，则直接执行
    if (isset($hooks['app_init'])) {
        foreach ($hooks['app_init'] as $k => $v) {
            Hook::exec($v, 'app_init');
        }
    }
    Hook::import($hooks, true);

});

/**
 * 获得插件自动加载的配置(暂停)
 * @param bool $truncate 是否清除手动配置的钩子
 * @return array
 */
function get_addon_autoload_config($truncate = false)
{
    // 读取addons的配置
    $config = (array)Config::get('addons.');
    if ($truncate) {
        // 清空手动配置的钩子
        $config['hooks'] = [];
    }
    $route = [];

    // 读取插件目录及钩子列表
    $base = get_class_methods("\\think\\Addons");
    $base = array_merge($base, ['install', 'uninstall', 'enable', 'disable']);

}

// 注册类的根命名空间
Loader::addNamespace('addons', ADDON_PATH);
/**
 * 获取插件类的类名
 * @param string $name 插件名
 * @param string $type 返回命名空间类型
 * @param string $class 当前类名
 * @return string
 */
function get_addon_class($name, $type = 'hook', $class = null)
{
    $name = Loader::parseName($name);
    // 处理多级控制器情况
    if (!is_null($class) && strpos($class, '.')) {
        $class = explode('.', $class);

        $class[count($class) - 1] = Loader::parseName(end($class), 1);
        $class = implode('\\', $class);
    } else {
        $class = Loader::parseName(is_null($class) ? $name : $class, 1);
    }
    switch ($type) {
        case 'controller':
            $namespace = "\\addons\\" . $name . "\\controller\\" . $class;
            break;
        default:
            $namespace = "\\addons\\" . $name . "\\" . $class;
    }
    return class_exists($namespace) ? $namespace : '';
}

/**
 * 获取插件的单例
 * @param string $name 插件名
 * @return \think\Addons|null
 */
function get_addon_instance($name)
{
    static $_addons = [];
    if (isset($_addons[$name])) {
        return $_addons[$name];
    }
    $class = get_addon_class($name);
    if (class_exists($class)) {
        $_addons[$name] = new $class();
        return $_addons[$name];
    } else {
        return null;
    }
}

/**
 * 获取插件类的配置值值
 * @param string $name 插件名
 * @return array
 */
function get_addon_config($name)
{
    $addon = get_addon_instance($name);
    if (!$addon) {
        return [];
    }
    return $addon->getConfig($name);
}

