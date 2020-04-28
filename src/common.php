<?php
/**
 * User: alexzy<59254502@qq.com>
 * Date: 2020-04-24
 */


// 插件目录
define('ADDON_PATH', \think\facade\Env::get('root_path') . 'addons' . DIRECTORY_SEPARATOR);

// 定义直接执行插件方法的路由
\think\facade\Route::any('addons/:addon/[:controller]/[:action]', "\\think\\addons\\Route@execute");

// 如果插件目录不存在则创建
if (!is_dir(ADDON_PATH)) {
    @mkdir(ADDON_PATH, 0755, true);
}

// 注册类的根命名空间
\think\Loader::addNamespace('addons', ADDON_PATH);

// 监听addon_init 插件初始化
\think\facade\Hook::listen('addon_init');

\think\facade\Hook::add('app_init', function () {
    $hooks = \think\facade\App::isDebug() ? [] : \think\facade\Cache::get('hooks.', []);
    if (empty($hooks)) {
        $hooks = \think\facade\Config::get('addons.hooks');
        // 初始化钩子
        foreach ($hooks as $key => $values) {
            if (is_string($values)) {
                $values = explode(',', $values);
            } else {
                $values = (array)$values;
            }
            $hooks[$key] = array_filter(array_map('get_addon_class', $values));
        }
        \think\facade\Cache::set('hooks', $hooks);
    }
    //如果在插件中有定义app_init，则直接执行
    if (isset($hooks['app_init'])) {
        foreach ($hooks['app_init'] as $k => $v) {
            \think\facade\Hook::exec($v, 'app_init');
        }
    }
    \think\facade\Hook::import($hooks, true);
});
/**
 * 获取插件命名空间
 * @param $name
 * @param string $type
 * @param null $class
 * @return string
 */
function get_addon_class($name, $type = 'hook', $class = null)
{
    //字符串命名风格转换
    $name = \think\Loader::parseName($name);
    //多级控制器，class不为null 且 包含 .
    if (!is_null($class) && strpos($class, '.')) {
        //分割class
        $class = explode('.', $class);
        //将最后一个字符串转换明明风格
        $class[count($class) - 1] = \think\Loader::parseName(end($class), 1);
        //重新组合为字符串
        $class = implode('\\', $class);
    } else {
        //转换明明风格
        $class = \think\Loader::parseName(is_null($class) ? $name : $class, 1);
    }
    //获取命名空间
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
 * 获取插件单例
 * @param $name
 * @return \think\Addons|mixed
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
 * 获取插件基础信息
 * @param $name
 * @return array
 */
function get_addon_info($name)
{
    $addon = get_addon_instance($name);
    if (!$addon) {
        return [];
    }
    return $addon->getInfo($name);
}

function get_addon_config($name)
{
    $addon = get_addon_instance($name);
    if (!$addon) {
        return [];
    }
    return $addon->getConfig($name);
}

function addon_url($url, $vars = [], $suffix = true, $domain = false)
{
    //去掉左边多余 /
    $url = ltrim($url, '/');
    //获取插件名称
    $addon = substr($url, 0, stripos($url, '/'));
    //url参数字符串转换为数组
    if (!is_array($vars)) {
        parse_str($vars, $params);
        $vars = $params;
    }
    //筛选出：开头的参数
    $params = [];
    foreach ($vars as $k => $v) {
        if (substr($k, 0, 1) === ':') {
            $params[$k] = $v;
            unset($vars[$k]);
        }
    }
    $val = "@addons/{$url}";
    $config = get_addon_config($addon);

    $url = url($val, $vars, $suffix, $domain);
    return $url;
}
