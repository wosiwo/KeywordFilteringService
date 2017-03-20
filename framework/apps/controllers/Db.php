<?php
namespace App\Controller;
use Swoole;

class Db extends Swoole\Controller
{
    function apt_test()
    {
        $apt = new Swoole\SelectDB($this->db);
        $apt->from('users');
        $apt->equal('id', 1);
        $res = $apt->getall();
        var_dump($res);
    }

    function tables()
    {
        /**
         * master database
         */
        $tables = $this->db->query("show tables")->fetchall();
        var_dump($tables);

        /**
         * other
         */
        $tables = $this->db("huya")->query("show tables")->fetchall();
        var_dump($tables);
    }

    function put()
    {
        $model = Model('User');
        $id = $model->put(array('name' => 'swoole', 'level' => 5, 'mobile' => '19999990000'));
        echo "insert id = $id\n";
    }

    function get()
    {
        $model = Model('User');
        $user = $model->get(1);
        /**
         * 打印数组
         */
        var_dump($user->get());

        $user->attach(new \App\Observer\ModelUpdate());

        /**
         * 修改mobile 为 13800008888
         */
        $user->mobile = '18948735886';
        $user->save();

        //删除此条记录
        //$user->delete();
    }

    function gets()
    {
        /**
         * @var $model \App\Model\User
         */
        $model = Model('User');
        //level = 5
//        $gets['id'] = $_GET['s'];
        $gets['where'][]  = 'id > '.$_GET['s'].'';
        //$gets['where'][] = array('id', '>', $_GET['s']);
        //仅获取数据
        var_dump($model->gets($gets));
        echo ($this->db->getSql());
        exit;

        //分页
        $gets['page'] = empty($_GET['page'])?1:intval($_GET['page']);
        $gets['pagesize'] = 5;
        $pager = null;
        $list = $model->gets($gets, $pager);

        foreach($list as $li)
        {
            echo "{$li['id']}: {$li['name']}<br/>\n";
        }
        //上一页/下一页
        echo $pager->render();
    }

    function codb()
    {
        $ret1 = $this->codb->query("show tables");
        $ret2 = $this->codb->query("desc user_login");

        $this->codb->wait(1.0);

        var_dump($ret1->result->fetchall());
        var_dump($ret2->result->fetchall());
    }
}