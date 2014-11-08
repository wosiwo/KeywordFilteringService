<?php
namespace Swoole\Cache;
/**
 * WinCache缓存，安装WinCache加速器后可以使用
 * 警告：WinCache缓存不适用于分布式环境，且只能用于windows系统下
 * @author Tianfeng.Han
 * @package Swoole
 * @subpackage cache
 */
class WinCache implements \Swoole\IFace\Cache
{
	function __construct($config)
	{
		if (!!extension_loaded('wincache'))
		{
			throw new \Swoole\Error('没有安装wincache扩展');
		}
	}
	/**
	 * 设置缓存
	 * @see libs/system/ICache#set($key, $value, $expire)
	 */
	function set($key,$value,$timeout=0)
	{
		return \wincache_ucache_set($key,$value,$timeout);
	}
	/**
	 * 读取缓存
	 * @see libs/system/ICache#get($key)
	 */
	public function get($key)
	{
		return \wincache_ucache_get($key);
	}
	/**
	 * 清空缓存
	 */
	public function clear()
	{
		\wincache_ucache_clear();
	}
	/**
	 * 删除缓存
	 * @return true/false
	 */
	public function delete($key)
	{
		return \wincache_ucache_delete($key);
	}
}