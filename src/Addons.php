<?php
/**
 * User: alexzy<59254502@qq.com>
 * Date: 2020-04-24
 */

namespace think;


/**
 * 插件基类
 * Class Addons
 * @package think
 */
abstract class Addons
{
    //错误信息
    protected $error;

    // 信息前缀
    protected $infoRange = 'addoninfo.';

    // 配置前缀
    protected $configRange = 'addonconfig.';

    // 插件目录
    public $addons_path = '';

    public $view;
    /**
     * 获取当前插件名
     * @return string
     */
    final public function getName()
    {
        $data = explode('\\', get_class($this));
        return strtolower(array_pop($data));
    }

    /**
     * 构造方法
     * Addons constructor.
     */
    public function __construct()
    {
        $name = $this->getName();
        // 获取当前插件目录
        $this->addons_path = ADDON_PATH . $name . DIRECTORY_SEPARATOR;
        //设置view_path
        $config = ['view_path' => $this->addons_path];
        //合并配置文件
        $config = array_merge(\think\facade\Config::get('template.'), $config);
        $this->view = new View($config, \think\facade\Config::get('template.tpl_replace_string'));

        if (method_exists($this, 'initialize')) {
            $this->initialize();
        }
    }

    /**
     * 获取插件属性信息
     * @param string $name
     * @return array|mixed|string
     */
    final public function getInfo($name = '')
    {
        if (empty($name)) {
            $name = $this->getName();
        }
        $info = \think\facade\Config::get($this->infoRange . $name);
        if ($info) {
            return $info;
        }
        $infoFile = $this->addons_path . 'info.ini';
        if (is_file($infoFile)) {
            $info = \think\facade\Config::parse($infoFile, '', $this->infoRange . $name);
//            $info['url'] = addon_url($name);
        }
        \think\facade\Config::set($this->infoRange . $name, $info);
        return $info ? $info : [];
    }

    /**
     * 获取插件的配置数组
     * @param string $name
     * @return mixed
     */
    final public function getConfig($name = '')
    {
        if (empty($name)) {
            $name = $this->getName();
        }

        $config = \think\facade\Config::get($this->configRange . $name);
        if ($config) {
            return $config;
        }
        $infoFile = $this->addons_path . 'config.php';
        if (is_file($infoFile)) {
            $temp_arr = include $infoFile;
            foreach ($temp_arr as $key => $value) {
                $config[$value['name']] = $value['value'];
            }
            unset($temp_arr);
        }
        \think\facade\Config::set($this->configRange . $name,$config);

        return $config;
    }

    /**
     * 获取完整配置列表
     * @param string $name
     * @return array
     */
    final public function getFullConfig($name = '')
    {
        $fullConfigArr = [];
        if (empty($name)) {
            $name = $this->getName();
        }
        $config_file = $this->addons_path . 'config.php';
        if (is_file($config_file)) {
            $fullConfigArr = include $config_file;
        }
        return $fullConfigArr;
    }


    /**
     * 加载模板和页面输出 可以返回输出内容
     * @access public
     * @param string $template 模板文件名或者内容
     * @param array $vars 模板输出变量
     * @param array $replace 替换内容
     * @param array $config 模板参数
     * @return mixed
     * @throws \Exception
     */
    public function fetch($template = '', $vars = [], $config = [])
    {
        if (!is_file($template)) {
            $template = '/' . $template;
        }
        echo $this->view->fetch($template, $vars, $config);
    }

    /**
     * 渲染内容输出
     * @access public
     * @param string $content 内容
     * @param array $vars 模板输出变量
     * @param array $replace 替换内容
     * @param array $config 模板参数
     * @return mixed
     */
    public function display($content, $vars = [], $config = [])
    {
        echo $this->view->display($content, $vars, $config);
    }

    /**
     * 渲染内容输出
     * @param $content
     * @param array $vars
     */
    public function show($content, $vars = [])
    {
        echo $this->view->fetch($content, $vars, [], true);
    }

    /**
     * 模板变量赋值
     * @access protected
     * @param mixed $name 要显示的模板变量
     * @param mixed $value 变量的值
     * @return void
     */
    public function assign($name, $value = '')
    {
        echo $this->view->assign($name, $value);
    }

    /**
     * 获取当前错误信息
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

    //必须安装插件方法
    abstract public function install();

    //必须卸载插件方法
    abstract public function uninstall();
}