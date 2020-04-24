<?php
/**
 * User: alexzy<59254502@qq.com>
 * Date: 2020-04-24
 */
use think\Loader;
class common
{
    /**
     * 获取插件类的类名
     * @param string $name  插件名
     * @param string $type  返回命名空间类型
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
     * @return mixed|null
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

}