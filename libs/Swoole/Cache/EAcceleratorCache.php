<?php
namespace Swoole\Cache;
/**
 * EAccelerator缓存，安装EAccelerator加速器后可以使用
 * 警告：EAccelerator缓存不适用于分布式环境
 * @author Tianfeng.Han
 * @package Swoole
 * @subpackage cache
 */
class EAcceleratorCache implements \Swoole\IFace\Cache
{
	function __construct($config)
	{
		if (!function_exists('eaccelerator_get'))
		{
			throw new \Swoole\Error('EAccelerator extension didn\'t installed');
		}
	}
	/**
	 * 设置缓存
	 * @see libs/system/ICache#set($key, $value, $expire)
	 */
	function set($key,$value,$timeout=0)
	{
		return \eaccelerator_put($key,$value,$timeout);
	}
	/**
	 * 读取缓存
	 * @see libs/system/ICache#get($key)
	 */
	public function get($key)
	{
		return \eaccelerator_get($key);
	}
	/**
	 * 清空缓存
	 */
	public function clear()
	{
		\eaccelerator_clear();
	}
	/**
	 * 删除缓存
	 * @return true/false
	 */
	public function delete($key)
	{
		return \eaccelerator_rm($key);
	}
}