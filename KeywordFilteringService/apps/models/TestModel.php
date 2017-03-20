<?php
/**
 * 微信绑定
 */

namespace KeywordModel;

use Swoole\Model;


class TestModel extends Model
{
    /**
     * 表名
     */
    public $table = "user_table";
    public $primary = "uid";



    /*
     * 数据库字段
     */
    const  F_uid = 'uid',  F_ctime = 'ctime';

    /**
     *
     * @return $this
     */
    static function getInstance() {
        return new self(\Swoole::$php);
    }


    function test($uids){
        return $uids;
    }


    public function dGetByUid($uid)
    {
        $params = array(
            self::F_uid => $uid,
        );
        $user = $this->getOne($params);

        return $user;

    }


    function insert($data)
    {
        if (!isset($data[self::F_ctime])) {
            $data[self::F_ctime] = time();
        }
        $rs = $this->put($data);

        return $rs ? $data[self::F_uid] : 0;
    }


    function getByUids($uids)
    {
        if (!is_array($uids)) {
            $uids = explode(',', $uids);
        }
        $rs = array();

        $params = array(
            'in' => array(self::F_uid, $uids),
        );
        $rs = $this->gets($params);
        return $rs;
    }

    public function updateByUid($uid, $set)
    {
        $params = array(
            self::F_uid => $uid,
        );
        $status = $this->sets($set,$params);

        return $status;
    }

    public function getPage($start, $limit)
    {
        $params = array(
            'order' => self::F_uid. 'desc',
            'limit' => $start.','.$limit,
        );
        $list = $this->gets($params);

        return $list;
    }

    public function getCount()
    {
        $params = array();
        return $this->count($params);
    }


}

