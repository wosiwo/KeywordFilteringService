<?php
namespace Swoole;
/**
 * 模型加载器
 * 产生一个模型的接口对象
 * @author Tianfeng.Han
 * @package SwooleSystem
 * @subpackage MVC
 */
class ModelLoader
{
	private $swoole = null;
	public $_models = array();

	function __construct($swoole)
	{
		$this->swoole = $swoole;
	}

	function __get($model_name)
	{
		if(isset($this->_models[$model_name]))
		return $this->_models[$model_name];
		else return $this->load($model_name);
	}

	function load($model_name)
	{
        $model_file = \Swoole::$app_path."/models/$model_name.model.php";
        if (is_file($model_file))
        {
            $model_class = $model_name;
            goto found_model;
        }
        $model_file = \Swoole::$app_path.'/models/'.$model_name.'.php';
        if (is_file($model_file))
		{
			$model_class = '\\App\\Model\\'.$model_name;
            goto found_model;
		}
		throw new Error("不存在的模型, <b>$model_name</b>");

        found_model:
        require_once($model_file);
        $this->_models[$model_name] = new $model_class($this->swoole);
        return $this->_models[$model_name];
	}
}
