<?php

namespace app\controller\common;

use think\Request;
use app\BaseController;

class Base extends BaseController
{
    // 是否自动实例化当前模型
    protected $autoNewModel = true;

    // 自定义实例化模型位置
    protected $ModelPath = null;

    // 实例化当前模型
    protected $M = null;

    // 是否开启自动验证
    protected $autoValidateCheck = true;

    // 不需要验证的方法
    protected $excludeValidateCheck = [];

    // 验证场景配置
    protected $autoValidateScenes = [];

    // 当前控制器信息
    protected $Cinfo = [];

    /**
     * 1. 自动实例化当前model
     */
    public function __construct(Request $request)
    {
        // 初始化控制器相关信息
        $this->initControllerInfo($request);
        // 实例化当前模型
        $this->getCurrentModel();
        // 自动验证
        $this->autoValidateAction();
    }

    // 初始化控制器相关信息
    // 获取当前访问的 控制器名称，路径，以及方法名
    public function initControllerInfo($request)
    {
        $str = $request->controller();
        // 获取真实控制器名称
        //  $arr = explode('.',$str);
        //  $Cname = $arr[count($arr)-1];
        $this->Cinfo = [
            'Cname' => class_basename($this),
            'Cpath' => str_replace('.', '\\', $str),
            // 当前方法
            'action' => $request->action()
        ];
    }

    // 获取当前模型实例
    public function getCurrentModel()
    {
        // 当前模型未实例化 并且需要自动实例化
        if (!$this->M && $this->autoNewModel) {
            $model = $this->ModelPath ? $this->ModelPath : $this->Cinfo['Cpath'];
            $this->M = app('app\model\\' . $model);
        }
    }

    // 自动化验证
    public function autoValidateAction()
    {
        // app地址
        define('__APP_PATH__', __DIR__ . '/../../');
        $action = $this->Cinfo['action'];
        // 判断是否需要验证
        // 系统已开启自动验证，并且当前方法不在 不需要验证的方法 里面（就是这个方法需要验证）

        if ($this->autoValidateCheck && !in_array($action, $this->excludeValidateCheck)) {
            // 获取验证实例
            // "app\controller\common/../../validate/admin\Manager.php" !=> "app\validate\Manager.php"
            // 假如这个验证文件存在
            // file_exists() 函数检查文件或目录是否存在。如果指定的文件或目录存在则返回 true，否则返回 false
            $validateName = file_exists(__APP_PATH__ . 'validate/' . $this->Cinfo['Cpath'] . '.php') ? $this->Cinfo['Cpath'] : $this->Cinfo['Cname'];

            $validate = app('app\validate\\' . $validateName);

            // 获取验证场景
            $scene = $action;
            // array_key_exists() 函数检查某个数组中是否存在指定的键名，如果键名存在则返回 true，如果键名不存在则返回 false。
            if (array_key_exists($action, $this->autoValidateScenes)) {
                $scene = $this->autoValidateScenes[$action];
            }

            // 开始验证
            $params = request()->param();
            if (!$validate->scene($scene)->check($params)) { // $scene = login,因此走了login场景验证
                // 抛出异常
                ApiException($validate->getError());
            }
        }
    }
}
