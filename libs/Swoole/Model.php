<?php
namespace Swoole;
/**
 * Model类，ORM基础类，提供对某个数据库表的接口
 * @author Administrator
 * @package SwooleSystem
 * @subpackage Model
 * @link http://www.swoole.com/
 */
class Model
{
	public $_data=array(); //数据库字段的具体值
    /**
     * @var IDatabase
     */
    public $db;
	public $swoole;

	public $primary="id";
	public $foreignkey='catid';

	public $_struct;
	public $_form;
	public $_form_secret = true;

	public $table="";
	/**
	 * 表切片参数
	 * @var unknown_type
	 */
	public $tablesize = 1000000;
	public $fields;
	public $select='*';

	public $create_sql='';

	public $if_cache = false;

	function __construct($swoole)
	{
		$this->db = $swoole->db;
		$this->dbs = new \Swoole\SelectDB($swoole->db);
		$this->swoole = $swoole;
	}
	/**
	 * 按ID切分表
	 * @param $id
	 * @return unknown_type
	 */
    function shard_table($id)
    {
        $table_id = intval($id/$this->tablesize);
        $this->table = $this->table.'_'.$table_id;
    }
	/**
	 * 获取主键$primary_key为$object_id的一条记录对象(Record Object)
	 * 如果参数为空的话，则返回一条空白的Record，可以赋值，产生一条新的记录
	 * @param $object_id
	 * @return Record Object
	 */
	public final function get($object_id=0,$where='')
	{
		return new Record($object_id,$this->db,$this->table,$this->primary,$where,$this->select);
	}
	/**
	 * 获取表的一段数据，查询的参数由$params指定
	 * @param $params
     * @param $pager Pager
	 * @return Array
	 */
	public final function gets($params, &$pager=null)
	{
	    if(empty($params)) return false;
		$selectdb = new SelectDB($this->db);
		$selectdb->from($this->table);
		$selectdb->primary = $this->primary;
		$selectdb->select($this->select);
		if(!isset($params['order'])) $params['order'] = "`{$this->table}`.{$this->primary} desc";
		$selectdb->put($params);
		if(isset($params['page']))
		{
			$selectdb->paging();
			$pager = $selectdb->pager;
		}
		return $selectdb->getall();
	}
	/**
	 * 插入一条新的记录到表
	 * @param $data Array 必须是键值（表的字段对应值）对应
	 * @return None
	 */
	public final function put($data)
	{
		if(empty($data) or !is_array($data)) return false;
		$this->db->insert($data, $this->table);
		return $this->db->lastInsertId();
	}
	/**
	 * 更新ID为$id的记录,值为$data关联数组
	 * @param $id
	 * @param $data
	 * @param $where 指定匹配字段，默认为主键
	 * @return true/false
	 */
	public final function set($id, $data, $where='')
	{
		if(empty($where)) $where=$this->primary;
		return $this->db->update($id,$data,$this->table,$where);
	}
	/**
	 * 更新一组数据
	 * @param $data 更新的数据
	 * @param $params update的参数列表
	 * @return true
	 */
	public final function sets($data,$params)
	{
		if(empty($params))
		{
			throw new \Exception("Model sets params is empty!");
			return false;
		}
		$selectdb = new SelectDB($this->db);
		$selectdb->from($this->table);
		$selectdb->put($params);
		$selectdb->update($data);
		return true;
	}
	/**
	 * 删除一条数据主键为$id的记录，
	 * @param $id
	 * @param $where 指定匹配字段，默认为主键
	 * @return true/false
	 */
	public final function del($id, $where=null)
	{
		if($where==null) $where = $this->primary;
		return $this->db->delete($id,$this->table,$where);
	}
    /**
     * 删除一条数据包含多个参数
     * @param array $params
     * @return true/false
     */
    public final function dels($params)
    {
        if(empty($params))
        {
            throw new \Exception("Model dels params is empty!");
            return false;
        }
    	$selectdb = new SelectDB($this->db);
        $selectdb->from($this->table);
		$selectdb->put($params);
        $selectdb->delete();
        return true;
    }
    /**
     * 返回符合条件的记录数
     * @param array $params
     * @return true/false
     */
    public final function count($params)
    {
    	$selectdb = new SelectDB($this->db);
		$selectdb->from($this->table);
		$selectdb->put($params);
		return $selectdb->count();
    }
	/**
	 * 获取到所有表记录的接口，通过这个接口可以访问到数据库的记录
	 * @return RecordSet Object (这是一个接口，不包含实际的数据)
	 */
	public final function all()
	{
		return new RecordSet($this->db, $this->table, $this->primary, $this->select);
	}
	/**
	 * 建立表，必须在Model类中，指定create_sql
	 * @return None
	 */
	function createTable()
	{
		if($this->create_sql) return $this->db->query($this->create_sql);
		else return false;
	}
	/**
	 * 获取表状态
	 * @return array 表的status，包含了自增ID，计数器等状态数据
	 */
	public final function getStatus()
	{
		return $this->db->query("show table status from ".DBNAME." where name='{$this->table}'")->fetch();
	}
	/**
	 * 获取一个数据列表，功能类似于gets，此方法仅用于SiaoCMS，不作为同样类库的方法
	 * @param $params
	 * @param $get
	 * @return unknown_type
	 */
	function getList(&$params,$get='data')
	{
		$selectdb = new SelectDB($this->db);
		$selectdb->from($this->table);
		$selectdb->select($this->select);
		$selectdb->limit(isset($params['row'])?$params['row']:10);
		unset($params['row']);
		$selectdb->order(isset($params['order'])?$params['order']:$this->primary.' desc');
		unset($params['order']);

		if(isset($params['typeid']))
		{
			$selectdb->where($this->foreignkey.'='.$params['typeid']);
			unset($params['typeid']);
		}
		$selectdb->put($params);
		if(array_key_exists('page',$params))
		{
			$selectdb->paging();
			global $php;
			$php->env['page'] = $params['page'];
			$php->env['start'] = 10*intval($params['page']/10);
			if($selectdb->pages>10 and $params['page']< $php->env['start'])
            {
                $php->env['more'] = 1;
            }
			$php->env['end'] = $selectdb->pages-$php->env['start'];
			$php->env['pages'] = $selectdb->pages;
			$php->env['pagesize'] = $selectdb->page_size;
			$php->env['num'] = $selectdb->num;
		}
		if($get==='data') return $selectdb->getall();
		elseif($get==='sql') return $selectdb->getsql();
	}
	/**
	 * 获取一个键值对应的结构，键为表记录主键的值，值为记录数据或者其中一个字段的值
	 * @param $gets
	 * @param $field
	 * @return unknown_type
	 */
	function getMap($gets, $field=null)
	{
	    $list = $this->gets($gets);
	    $new = array();
	    foreach($list as $li)
	    {
	        if(empty($field)) $new[$li[$this->primary]] = $li;
	        else $new[$li[$this->primary]] = $li[$field];
	    }
	    unset($list);
	    return $new;
	}
	/**
	 * 获取一个2层的树状结构
	 * @param $gets
	 * @param $category
	 * @param $order
	 * @return unknown_type
	 */
	function getTree($gets,$category='fid',$order='id desc')
	{
	    $gets['order'] = $category.','.$order;
	    $list = $this->gets($gets);
	    foreach($list as $li)
	    {
	        if($li[$category]==0) $new[$li[$this->primary]] = $li;
	        else $new[$li[$category]]['child'][$li[$this->primary]] = $li;
	    }
	    unset($list);
	    return $new;
	}
	/**
	 * 检测是否存在数据，实际可以用count代替，0为false，>0为true
	 * @param $gets
	 * @return unknown_type
	 */
	function exists($gets)
	{
	    $c = $this->count($gets);
	    if($c>0) return true;
	    else return false;
	}
	/**
	 * 获取表的字段描述
	 * @return $fields
	 */
	function desc()
	{
		return $this->db->query('describe '.$this->table)->fetchall();
	}
    /**
     * 自动生成表单
     * @param $set_id
     * @return unknown_type
     */
	function getForm($set_id=0)
	{
	    $this->_form_();
	    //传入ID，修改表单
	    if($set_id)
	    {
	        $data = $this->get((int)$set_id)->get();
	        foreach($this->_form as $k=>&$f) $f['value'] = $data[$k];
            if(method_exists($this,"_set_")) $this->_set_();

            if($this->_form_secret) Form::secret(get_class($this).'_set');
	    }
	    //增加表单
	    elseif(method_exists($this,"_add_"))
	    {
	        $this->_add_();
	        if($this->_form_secret) Form::secret(get_class($this).'_add');
	    }
        return Form::autoform($this->_form);
	}
	/**
	 *
	 * @param 出错时设置$error
	 * @return true or false
	 */
    function checkForm($input,$method,&$error)
    {
        if($this->_form_secret)
        {
            $k = 'form_'.get_class($this).'_'.$method;
            if(!isset($_SESSION)) session();
            if($_COOKIE[$k]!=$_SESSION[$k])
            {
                $error = '错误的请求';
                return false;
            }
        }
        $this->_form_();
        return Form::checkInput($input,$this->_form,$error);
    }
	function parseForm()
	{

	}
}
/**
 * Record类，表中的一条记录，通过对象的操作，映射到数据库表
 * 可以使用属性访问，也可以通过关联数组方式访问
 * @author Tianfeng.Han
 * @package SwooleSystem
 * @subpackage Model
 */
class Record implements \ArrayAccess
{
	public $_data = array();
	public $_change;
    /**
     * @var \Swoole\Database
     */
    public $db;

	public $primary="id";
	public $table="";

	public $change=0;
	public $_current_id = 0;
	public $_currend_key;

	function __construct($id, $db, $table, $primary, $where='', $select='*')
	{
		$this->db=$db;
		$this->_current_id=$id;
		$this->table=$table;
		$this->primary=$primary;
        if (empty($where)) $where = $primary;
		if(!empty($this->_current_id))
		{
			$res=$this->db->query("select $select from ".$this->table.' where '.$where."='$id' limit 1");
			$this->_data = $res->fetch();
            $this->_current_id = $this->_data[$this->primary];
			if(!empty($this->_data)) $this->change=1;
		}
	}
	/**
	 * 将关联数组压入object中，赋值给各个字段
	 * @param $data
	 * @return unknown_type
	 */
	function put($data)
	{
		if($this->change == 1)
		{
			$this->change = 2;
			$this->_change = $data;
		}
		elseif($this->change==0)
		{
			$this->change = 1;
			$this->_data=$data;
		}
	}
	/**
	 * 获取数据数组
	 * @return unknown_type
	 */
	function get()
	{
		return $this->_data;
	}

	function __get($property)
	{
		if(array_key_exists($property, $this->_data)) return $this->_data[$property];
		else Error::pecho("Record object no property: $property");
	}

	function __set($property, $value)
	{
		if($this->change==1 or $this->change==2)
		{
            $this->change = 2;
            $this->_change[$property] = $value;
            $this->_data[$property] = $value;
		}
		else
		{
            $this->_data[$property] = $value;
		}
		return true;
	}
	/**
	 * 保存对象数据到数据库
	 * 如果是空白的记录，保存则会Insert到数据库
	 * 如果是已存在的记录，保持则会update，修改过的值，如果没有任何值被修改，则不执行SQL
	 * @return unknown_type
	 */
	function save()
	{
		if($this->change==0 or $this->change==1)
		{
			$ret = $this->db->insert($this->_data, $this->table);
            if($ret === false) return false;
            //改变状态
            $this->change = 1;
			$this->_current_id = $this->db->lastInsertId();
		}
		elseif($this->change==2)
		{
			$update = $this->_data;
			unset($update[$this->primary]);
			return $this->db->update($this->_current_id,$this->_change,$this->table,$this->primary);
		}
		return true;
	}
	function update()
	{
		$update = $this->_data;
		unset($update[$this->primary]);
		$this->db->update($this->_current_id,$this->_change,$this->table,$this->primary);
	}
	/**
	 * 删除数据库中的此条记录
	 * @return unknown_type
	 */
	function delete()
	{
		$this->db->delete($this->_current_id,$this->table,$this->primary);
	}

	function offsetExists($keyname)
	{
		return array_key_exists($keyname,$this->_data);
	}

	function offsetGet($keyname)
	{
		return $this->_data[$keyname];
	}

	function offsetSet($keyname,$value)
	{
		$this->_data[$keyname] = $value;
	}

	function offsetUnset($keyname)
	{
		unset($this->_data[$keyname]);
	}
}
/**
 * 数据结果集，由Record组成
 * 通过foreach遍历，可以产生单条的Record对象，对每条数据进行操作
 * @author Tianfeng.Han
 * @package SwooleSystem
 * @subpackage Model
 */
class RecordSet implements \Iterator
{
    protected $_list = array();
    protected $table = '';
    protected $db;
    /**
     * @var SelectDb
     */
    protected $db_select;

    public $primary = "";

    public $_current_id = 0;

    function __construct($db,$table,$primary,$select)
	{
        $this->table = $table;
        $this->primary = $primary;
        $this->db = $db;
        $this->db_select = new SelectDB($db);
        $this->db_select->from($table);
        $this->db_select->primary = $primary;
        $this->db_select->select($select);
        $this->db_select->order($this->primary . " desc");
	}
	/**
	 * 获取得到的数据
     * @return array
	 */
	function get()
	{
		return $this->_list;
	}
	/**
	 * 制定查询的参数，再调用数据之前进行
	 * 参数为SQL SelectDB的put语句
	 * @param array $params
     * @return bool
	 */
	function params($params)
	{
		return $this->db_select->put($params);
	}
	/**
	 * 过滤器语法，参数为SQL SelectDB的where语句
	 * @param array $params
     * @return null
	 */
	function filter($where)
	{
		$this->db_select->where($where);
	}
	/**
	 * 增加过滤条件，$field = $value
	 * @return unknown_type
	 */
	function eq($field, $value)
	{
		$this->db_select->equal($field,$value);
	}
	/**
	 * 过滤器语法，参数为SQL SelectDB的orwhere语句
	 * @param unknown_type $params
	 */
	function orfilter($where)
	{
		$this->db_select->orwhere($where);
	}
	/**
	 * 获取一条数据
	 * 参数可以制定返回的字段
	 * @param $field
	 */
	function fetch($field='')
	{
		return $this->db_select->getone($field);
	}
	/**
	 * 获取全部数据
	 */
	function fetchall()
	{
		return $this->db_select->getall();
	}

    function __set($key, $v)
    {
        $this->db_select->$key = $v;
    }

	function __call($method,$argv)
	{
		return call_user_func_array(array($this->db_select,$method),$argv);
	}

	public function rewind()
	{
		if(empty($this->_list)) $this->_list = $this->db_select->getall();
		$this->_current_id=0;
	}

	public function key()
	{
		return $this->_current_id;
	}

	public function current()
	{
		$record = new Record(0,$this->db,$this->table,$this->primary);
		$record->put($this->_list[$this->_current_id]);
		$record->_current_id = $this->_list[$this->_current_id][$this->primary];
		return $record;
	}

	public function next()
	{
		$this->_current_id++;
	}

	public function valid()
	{
		if(isset($this->_list[$this->_current_id])) return true;
		else return false;
	}
}
?>