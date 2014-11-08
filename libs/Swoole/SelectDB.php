<?php
namespace Swoole;
/**
 * 查询数据库的封装类，基于底层数据库封装类，实现SQL生成器
 * @author Tianfeng.Han
 * @package SwooleSystem
 * @subpackage dabase
 */
class SelectDB
{
    static $error_call = '';
    static $allow_regx = '#^([a-z0-9\(\)\._=\-\+\*\`\s\'\",]+)$#i';

    public $table = '';
    public $primary = 'id';
    public $select = '*';
    public $sql = '';
    public $limit = '';
    public $where = '';
    public $order = '';
    public $group = '';
    public $use_index = '';
    public $having = '';
    public $join = '';
    public $union = '';
    public $for_update = '';

    //Union联合查询
    private $if_union = false;
    private $union_select = '';

    //Join连接表
    private $if_join = false;
    private $if_add_tablename = false;

    //Count计算
    private $count_fields = '*';

    public $page_size = 10;
    public $num = 0;
    public $pages = 0;
    public $page = 0;
    public $pager = null;

    public $auto_cache = false;
    public $cache_life = 600;
    public $cache_prefix = 'selectdb';

    public $RecordSet;

    public $is_execute = 0;

    public $result_filter = array();

    public $call_by = 'func';

    /**
     * @var \Swoole\Database
     */
    public $db;

    function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * 初始化，select的值，参数$where可以指定初始化哪一项
     * @param $what
     * @return unknown_type
     */
    function init($what='')
    {
        if($what=='')
        {
            $this->table='';
            $this->primary='id';
            $this->select='*';
            $this->sql='';
            $this->limit='';
            $this->where='';
            $this->order='';
            $this->group='';
            $this->use_index='';
            $this->join='';
            $this->union='';
        }
        else
        $this->$what = '';
    }
    /**
     * 字段等于某个值，支持子查询，$where可以是对象
     * @param $field
     * @param $where
     * @return unknown_type
     */
    function equal($field,$_where)
    {
        if($_where instanceof SelectDB)
        {
            $where = $field.'=('.$_where->getsql().')';
        }
        else
        {
            $where = "`$field`='$_where'";
        }
        $this->where($where);
    }
    /**
     * 指定表名，可以使用table1,table2
     * @param $table_name
     * @return null
     */
    function from($table)
    {
        if (strpos($table,"`") === false)
        {
            $this->table= "`".$table."`";
        }
        else{
            $this->table=$table;
        }
    }
    /**
     * 指定查询的字段，select * from table
     * 可多次使用，连接多个字段
     * @param $select
     * @return null
     */
    function select($select,$force=false)
    {
        if($this->select=="*" or $force) $this->select=$select;
        else $this->select=$this->select.','.$select;
    }
    /**
     * where参数，查询的条件
     * @param $where
     * @return null
     */
    function where($where)
    {
        //$where = str_replace(' or ','',$where);
        if($this->where=="")
        {
            $this->where="where ".$where;
        }
        else
        {
            $this->where=$this->where." and ".$where;
        }
    }
    /**
     * 指定查询所使用的索引字段
     * @param $fields
     * @return null
     */
    function useIndex($field)
    {
        self::sql_safe($field);
        $this->use_index = "use index($field)";
    }
    /**
     * 相似查询like
     * @param $field
     * @param $like
     * @return null
     */
    function like($field,$like)
    {
        self::sql_safe($field);
        $this->where("`{$field}` like '{$like}'");
    }
    /**
     * 使用or连接的条件
     * @param $where
     * @return null
     */
    function orwhere($where)
    {
        if($this->where=="")
        {
            $this->where="where ".$where;
        }
        else
        {
            $this->where=$this->where." or ".$where;
        }
    }
    /**
     * 查询的条数
     * @param $limit
     * @return null
     */
    function limit($limit)
    {
        if(!empty($limit))
        {
            $_limit = explode(',', $limit, 2);
            if(count($_limit)==2) $this->limit='limit '.(int)$_limit[0].','.(int)$_limit[1];
            else $this->limit="limit ".(int)$limit;
        }
        else $this->limit = '';
    }
    /**
     * 指定排序方式
     * @param $order
     * @return null
     */
    function order($order)
    {
        if(!empty($order))
        {
            self::sql_safe($order);
            $this->order="order by $order";
        }
        else $this->order = '';
    }
    /**
     * 组合方式
     * @param $group
     * @return null
     */
    function group($group)
    {
        if(!empty($group))
        {
            self::sql_safe($group);
            $this->group = "group by $group";
        }
        else $this->group = '';
    }
    /**
     * group后条件
     * @param $having
     * @return null
     */
    function having($having)
    {
        if(!empty($having))
        {
            self::sql_safe($having);
            $this->having = "having $having";
        }
        else $this->having = '';
    }
    /**
     * IN条件
     * @param $field
     * @param $ins
     * @return null
     */
    function in($field, $ins)
    {
        $ins = trim($ins, ','); //去掉2面的分号
        $this->where("`$field` in ({$ins})");
    }
    /**
     * NOT IN条件
     * @param $field
     * @param $ins
     * @return null
     */
    function notin($field,$ins)
    {
        $this->where("`$field` not in ({$ins})");
    }
    /**
     * INNER连接
     * @param $table_name
     * @param $on
     * @return null
     */
    function join($table_name,$on)
    {
        $this->join.="INNER JOIN `{$table_name}` ON ({$on})";
    }
    /**
     * 左连接
     * @param $table_name
     * @param $on
     * @return null
     */
    function leftjoin($table_name,$on)
    {
        $this->join.="LEFT JOIN `{$table_name}` ON ({$on})";
    }
    /**
     * 右连接
     * @param $table_name
     * @param $on
     * @return null
     */
    function rightjoin($table_name,$on)
    {
        $this->join.="RIGHT JOIN `{$table_name}` ON ({$on})";
    }
    /**
     * 分页参数,指定每页数量
     * @param $pagesize
     * @return null
     */
    function pagesize($pagesize)
    {
        $this->page_size = (int)$pagesize;
    }
    /**
     * 分页参数,指定当前页数
     * @param $page
     * @return null
     */
    function page($page)
    {
        $this->page = (int)$page;
    }
    /**
     * 主键查询条件
     * @param $id
     * @return null
     */
    function id($id)
    {
        $this->where("`{$this->primary}` = '$id'");
    }
    /**
     * 产生分页
     * @return null
     */
    function paging()
    {
        $this->num = $this->count();
        $offset=($this->page-1)*$this->page_size;
        if($offset<0) $offset=0;
        if($this->num%$this->page_size>0)
        $this->pages=intval($this->num/$this->page_size)+1;
        else
        $this->pages=$this->num/$this->page_size;
        $this->limit($offset.','.$this->page_size);
        $this->pager = new Pager(array('total'=>$this->num,'perpage'=>$this->page_size,'nowindex'=>$this->page));
    }

    /**
     * 使SQL元素安全
     * @param $sql_sub
     * @return null
     */
    static function sql_safe($sql_sub)
    {
        if(!preg_match(self::$allow_regx, $sql_sub))
        {
            echo $sql_sub;
            if(self::$error_call==='') die('sql block is not safe!');
            else call_user_func(self::$error_call);
        }
    }
    /**
     * 获取组合成的SQL语句字符串
     * @param $ifreturn
     * @return null
     */
    function getsql($ifreturn=true)
    {
        $this->sql="select {$this->select} from {$this->table} {$this->join} {$this->use_index} {$this->where} {$this->union} {$this->group} {$this->having} {$this->order} {$this->limit} {$this->for_update}";
        if($this->if_union) $this->sql = str_replace('{#union_select#}',$this->union_select,$this->sql);
        if($ifreturn) return $this->sql;
    }

    function raw_put($params)
    {
        foreach($params as $array)
        {
            if(isset($array[0]) and isset($array[1]) and count($array)==2)
            {
                $this->_call($array[0],$array[1]);
            }
            else
            {
                $this->raw_put($array);
            }
        }
    }
    /**
     * 锁定行或表
     * @return null
     */
    function lock()
    {
        $this->for_update = 'for update';
    }
    /**
     * 执行生成的SQL语句
     * @param $sql
     * @return null
     */
    function exeucte($sql='')
    {
        if($sql=='') $this->getsql(false);
        else $this->sql = $this->sql();
        $this->res = $this->db->query($this->sql);
        $this->is_execute++;
    }
    /**
     * SQL联合
     * @param $sql
     * @return null
     */
    function union($sql)
    {
        $this->if_union = true;
        if($sql instanceof SelectDB)
        {
            $this->union_select = $sql->select;
            $sql->select = '{#union_select#}';
            $this->union = 'UNION ('.$sql->getsql(true).')';
        }
        else $this->union = 'UNION ('.$sql.')';
    }
    /**
     * 将数组作为指令调用
     * @param $params
     * @return null
     */
    function put($params)
    {
        if(isset($params['put']))
        {
            Error::info('SelectDB Error!','Params put() cannot call put()!');
        }
        //处理where条件
        if(isset($params['where']))
        {
            $wheres = $params['where'];
            if(is_array($wheres)) foreach($wheres as $where) $this->where($where);
            else $this->where($wheres);
            unset($params['where']);
        }
        //处理orwhere条件
        if(isset($params['orwhere']))
        {
            $orwheres = $params['orwhere'];
            if(is_array($orwheres)) foreach($orwheres as $orwhere) $this->orwhere($orwhere);
            else $this->$orwheres($orwheres);
            unset($params['orwhere']);
        }
        //处理walk调用
        if(isset($params['walk']))
        {
            foreach($params['walk'] as $call)
            {
                list($key,$value) = each($call);
                $this->_call($key,$value);
            }
            unset($params['walk']);
        }
        //处理其他参数
        foreach($params as $key=>$value)
        {
            $this->_call($key,$value);
        }
    }
    private function _call($method,$param)
    {
        if($method=='update' or $method=='delete' or $method=='insert') return false;
        if(strpos($method,'_')!==0)
        {
            if(method_exists($this,$method))
            {
                if(is_array($param)) call_user_func_array(array($this,$method),$param);
                else $this->$method($param);
            }
            else
            {
                $param = $this->db->quote($param);
                if($this->call_by=='func') $this->where($method.'="'.$param.'"');
                elseif($this->call_by=='smarty')
                {
                    if(strpos($param,'$')===false)	$this->where($method."='".$param."'");
                    else $this->where($method."='{".$param."}'");
                }
                else Error::info('Error: SelectDB 错误的参数',"<pre>参数$method=$param</pre>");
            }
        }
    }
    /**
     * 获取记录
     * @param $field
     * @param $cache_id
     * @return unknown_type
     */
    function getone($field='',$cache_id='')
    {
        $this->limit('1');
        if($this->auto_cache or !empty($cache_id))
        {
            $cache_key = empty($cache_id)?$this->cache_prefix.'_one_'.md5($this->sql):$this->cache_prefix.'_all_'.$cache_id;
            global $php;
            $record = $php->cache->get($cache_key);
            if(empty($data))
            {
                if($this->is_execute==0) $this->exeucte();
                $record = $this->res->fetch();
                $php->cache->set($cache_key,$record,$this->cache_life);
            }
        }
        else
        {
            if($this->is_execute==0) $this->exeucte();
            $record = $this->res->fetch();
        }
        if($field==='') return $record;
        return $record[$field];
    }
    /**
     * 获取所有记录
     * @param $cache_id
     * @return unknown_type
     */
    function getall($cache_id='')
    {
        if($this->auto_cache or !empty($cache_id))
        {
            $cache_key = empty($cache_id)?$this->cache_prefix.'_all_'.md5($this->sql):$this->cache_prefix.'_all_'.$cache_id;
            global $php;
            $data = $php->cache->get($cache_key);
            if(empty($data))
            {
                if($this->is_execute==0) $this->exeucte();
                $data = $this->res->fetchall();
                $php->cache->set($cache_key,$data,$this->cache_life);
                return $data;
            }
            else return $data;
        }
        else
        {
            if($this->is_execute==0) $this->exeucte();
            return $this->res->fetchall();
        }
    }
    /**
     * 获取当前条件下的记录数
     * @return unknown_type
     */
    public function count()
    {
        $sql="select count({$this->count_fields}) as c from {$this->table} {$this->join} {$this->where} {$this->union} {$this->group}";
        if($this->if_union)
        {
            $sql = str_replace('{#union_select#}',"count({$this->count_fields}) as c",$sql);
            $c = $this->db->query($sql)->fetchall();
            $cc = 0;
            foreach($c as $_c)
            {
                $cc+=$_c['c'];
            }
            return intval($cc);
        }
        else
        {
            $c = $this->db->query($sql)->fetch();
            return intval($c['c']);
        }
    }
    /**
     * 执行插入操作
     * @param $data
     * @return unknown_type
     */
    function insert($data)
    {
        $field="";
        $values="";
        foreach($data as $key => $value)
        {
            $value = $this->db->quote($value);
            $field=$field."`$key`,";
            $values=$values."'$value',";
        }
        $field = substr($field, 0, -1);
        $values = substr($values, 0, -1);
        return $this->db->query("insert into {$this->table} ($field) values($values)");
    }
    /**
     * 对符合当前条件的记录执行update
     * @param $data
     * @return unknown_type
     */
    function update($data)
    {
        $update="";
        foreach($data as $key=>$value)
        {
            $value = $this->db->quote($value);
            if($value!='' and $value{0}=='`') $update=$update."`$key`=$value,";
            else $update=$update."`$key`='$value',";
        }
        $update = substr($update,0,-1);
        return $this->db->query("update {$this->table} set $update {$this->where} {$this->limit}");
    }
    /**
     * 删除当前条件下的记录
     * @return unknown_type
     */
    function delete()
    {
        return $this->db->query("delete from {$this->table} {$this->where} {$this->limit}");
    }
}
