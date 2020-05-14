<?php
/**
 * User: alexzy<59254502@qq.com>
 * Date: 2020-04-24
 */

namespace think\addons;


/**
 * 插件默认执行控制器
 * Class Route
 */
class Route
{
    public function execute($addon = null, $controller = null, $action = null)
    {
        // 是否自动转换控制器和操作名
        $convert = \think\facade\Config::get('url_convert');
        //开启自动转换则转为小写
        $filter = $convert ? 'strtolower' : 'trim';

        //插件名存在则执行过滤方法，否则返回空
        $addon = $addon ? trim(call_user_func($filter, $addon)) : '';
        //控制器名存在则执行过滤方法，否则返回空
        $controller = $controller ? trim(call_user_func($filter, $controller)) : 'index';
        //方法名存在则执行过滤方法，否则返回空
        $action = $action ? trim(call_user_func($filter, $action)) : 'index';
        //监听插件开始
        \think\facade\Hook::listen('addon_begin');
        //插件名 控制器名 方法名均不为空则执行
        if (!empty($addon) && !empty($controller) && !empty($action)) {
            //获取插件信息
            $info = get_addon_info($addon);
            if (!$info) {
                throw new \think\exception\HttpException(404, "插件" . $addon . "不存在");
            }
            if (!$info['state']) {
                throw new \think\exception\HttpException(500, "插件" . $addon . "已禁用");
            }
            // 设置当前请求的控制器、操作
            request()->setModule($addon)->setController($controller)->setAction($action);
            // 监听addon_module_init
            \think\facade\Hook::listen('addon_module_init');
            //获取控制器类
            $class = get_addon_class($addon, 'controller', $controller);
            if (!$class) {
                throw new \think\exception\HttpException(404, '插件控制器' . \think\Loader::parseName($controller, 1) . '不存在');
            }
            $instance = new $class();
            $vars = [];
            //方法存在
            if (is_callable([$instance, $action])) {
                // 执行操作方法
                $call = [$instance, $action];
            } elseif (is_callable([$instance, '_empty'])) {
                // 空操作，携带参数
                $call = [$instance, '_empty'];
                $vars = [$action];
            } else {
                // 操作不存在
                throw new \think\exception\HttpException(404, '插件方法 ' . get_class($instance) . '->' . $action . '()' . ' 不存在');
            }

            \think\facade\Hook::listen('addon_action_begin', $call);
            return call_user_func_array($call, $vars);
        } else {
            abort(500, "addon不能为空");
        }
    }

}