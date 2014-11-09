<?php
namespace My\Filter;

class Trie {
    //默认序列化后字典存放路径
    var $default_out_path ;
    //默认原始字典存放路径
    var $default_dict_path ;
    //节点数组。每个节点为二元组，依次为是否叶子节点，子节点.
    var $nodes ;

    function Trie($file) {
        $this->default_out_path  = $file['bin'];
        $this->default_dict_path = $file['source'];
    }

    /**
     * 构建树，存储序列化文本等操作封装
     * @param $path  string 字典存放位置
     * @param $out_path  string 序列化后存放位置
     * @return $ret mixed 是否成功，不成功返回false
     */
    function build($path = null, $out_path = null) {
        if(empty($path)) {
            $path = $this->default_dict_path;
        }
        if(empty($out_path)) {
            $out_path = $this->default_out_path;
        }

        $words = $this->getDict($path);
        $tree = $this->getTree($words);
        $ret = $this->putBinaryDict($out_path, $tree);
        $a = true;
        return $ret;
    }
    
    /**
     * 调用外部python脚本来过滤关键词
     */
    function checkKeyWord($content){
    	$url = FILTER_HTTP;
    	// $content = Common::changeCharset($content,'gbk','utf-8');
    	$post = array('text'=>$content);
    	$result = _CurlPost($url,$post);
    	
    	$result = json_decode($result,true);

    	if(empty($result) || !is_array($result)){
    		return false;
    	}
    	// $result = Common::changeCharset($result);
    	return $result;
    	
    }

    /**
     * 用于提供查找关键字的方法
     * @param $content string 需要查找的文本
     * @param $dict_path 序列化字典路径
     * @return $matchs array 查找到的关键字和权重
     */
    function search($content, $ifUpperCase=false) {
        $dict_path = $this->default_out_path;
		if ($this->nodes) {
			$matchs = $this->match($ifUpperCase,$content);
			return $matchs;
		} else {
			return false;
		}
    }

    /**
     * 将文件中的字典逐行放到数组中去
     * @param $path string 字典路径
     * @return $words array 字典
     */
    function getDict($path) {
        $i = 0;
        $words = array();

        $handle = fopen($path, "r");

        if($handle == false) {
            return $words;
        }
        while(!feof($handle)) {
            $words[$i] = trim(fgets($handle));
            $i++;
        }
        fclose($handle);
        return $words;
    }

    /**
     * 获取序列化后的字典并反序列化
     * @param $path string 序列化字典存放路径
     * @return $words array 反序列化后的数组
     */
    function getBinaryDict($path = null) {
        if(empty($path)) {
            $path = $this->default_out_path;
        }
		$words = self::readover($path);
        if(!$words) {
            return array();
        }
        $words = unserialize ($words);

        return $words;
    }

    /**
     * 读取文件
     *
     * @param string $fileName 文件绝对路径
     * @param string $method 读取模式
     */
    public static function readover($fileName, $method = 'rb') {
        // $fileName = S::escapePath($fileName);
        $data = '';
        if ($handle = @fopen($fileName, $method)) {
            flock($handle, LOCK_SH);
            $data = @fread($handle, filesize($fileName));
            fclose($handle);
        }
        return $data;
    }

    /**
     * 将字典序列化后保存到文件中
     * @param $path string 保存路径
     * @param $words array 数组形式的字典
     * @return $ret mixed 没有保存成功返回false
     */
    function putBinaryDict($path, $words) {
        if(empty($path)) {
            $path = $this->default_out_path;
        }
        if(!$words) {
            return ;
        }
        $words = serialize($words);
        $handle = fopen($path, 'wb');
        $ret = fwrite($handle, $words);
        if($ret == false) {
            return false;
        }
        fclose($handle);
        return $ret;

    }

    /**
     * 构建树的过程方法
     * @param $words array 字典和权重数组
     */
    function getTree($words) {
        $this->nodes = array( array(false, array()) ); //初始化，添加根节点
        $p = 1; //下一个要插入的节点号
        foreach ($words as $word) {
			$cur = 0; //当前节点号
			//preg_match('/^(.*?)\s+(.*)/i', $word, $weight); //提取关键字和权重
			//$weight = explode("|", $word);
			//$word = trim($weight[0]);
			list($word, $weight, $replace) = $this->split($word);
            // echo strlen($word);
			for ($len = strlen($word), $i = 0; $i < $len; $i++) {
				$c = ord($word[$i]);
                // echo $c."\n";
				if (isset($this->nodes[$cur][1][$c])) { //已存在就下移
					$cur = $this->nodes[$cur][1][$c];
					continue;
				}
				$this->nodes[$p]= array(false, array()); //创建新节点
				$this->nodes[$cur][1][$c] = $p; //在父节点记录子节点号
				$cur = $p; //把当前节点设为新插入的
				$p++; //
			}
			$this->nodes[$cur][0] = true; //一个词结束，标记叶子节点
			$this->nodes[$cur][2] = trim($weight); //将权重放在叶子节点
			$this->nodes[$cur][3] = trim($replace);
		}
		return $this->nodes;
	}

	function split($str) {
		if (($pos = strrpos($str, '|')) === false) {
			return array($str, 0);
		}
		return explode('|',$str);
	}

    /**
     * 用于搜索关键字的方法
     * @param $s string 需要查找的文本
     * @return $ret array 查找到的关键词及权重
     */
    function match($ifUppCase,$s) {
		$ifUppCase == 1 && $s = mb_strtolower($s,'UTF-8');
        $isUTF8 = true;
        $ret = array();
        $cur = 0; //当前节点，初始为根节点
        $i = 0; //字符串当前偏移
        $p = 0; //字符串回溯位置
        $len = strlen($s);
        while($i < $len) {
            $c = ord($s[$i]);
            if (isset($this->nodes[$cur][1][$c])) { //如果存在
                $cur = $this->nodes[$cur][1][$c]; //下移当前节点
                if ($this->nodes[$cur][0]) { //是叶子节点，单词匹配！
                    $ret[$p] = array(substr($s, $p, $i - $p + 1), $this->nodes[$cur][2]); //取出匹配位置和匹配的词以及词的权重
                    $p = $i + 1; //设置下一个回溯位置
                    $cur = 0; //重置当前节点为根节点
                }
				$i++; //下一个字符
            } else { //不匹配
				$cur = 0; //重置当前节点为根节点
                if (!$isUTF8 && ord($s[$p]) > 127 && ord($s[$p+1]) > 127) {
					$p += 2; //设置下一个回溯位置
				} else {
					$p += 1; //设置下一个回溯位置
				}
				$i = $p; //把当前偏移设为回溯位置
            }
        }
        return $ret;    
    }

 	function replaces($s,$ifUppCase = 0) {
    	$ifUppCase && $s = strtolower($s);
        $isUTF8 = strtoupper(substr($GLOBALS['db_charset'],0,3)) === 'UTF' ? true : false;
        $ret = array();
        $cur = 0; //当前节点，初始为根节点
        $i = 0; //字符串当前偏移
        $p = 0; //字符串回溯位置
        $len = strlen($s);
        while($i < $len) {
            $c = ord($s[$i]);
            if (isset($this->nodes[$cur][1][$c])) { //如果存在
                $cur = $this->nodes[$cur][1][$c]; //下移当前节点
                if ($this->nodes[$cur][0]) { //是叶子节点，单词匹配！
                    $s = ($this->nodes[$cur][2] == 0.6 && isset($this->nodes[$cur][3])) ? substr_replace($s, $this->nodes[$cur][3], $p, $i - $p + 1) : $s; //取出匹配位置和匹配的词以及词的权重
                    $p = $i + 1; //设置下一个回溯位置
                    $cur = 0; //重置当前节点为根节点
                }
				$i++; //下一个字符
            } else { //不匹配
				$cur = 0; //重置当前节点为根节点
                if (!$isUTF8 && ord($s[$p]) > 127 && ord($s[$p+1]) > 127) {
					$p += 2; //设置下一个回溯位置
				} else {
					$p += 1; //设置下一个回溯位置
				}
				$i = $p; //把当前偏移设为回溯位置
            }
        }
        return $s;    
    }
}

/**
 * 根据给定词语权重对文档进行评分，目前使用Bayes算法，不考虑词频影响
 * 算法如下：
 * 假设文档中有分词t1,t2,t3,……tn,其权重分别为w1,w2,w3,……,wn
 * 则根据Bayes算法，文档权重为：
 * 设p1 = w1*w2*w3*……*wn
 * 设p2 = (1-w1)*(1-w2)*(1-w3)*……*(1-wn)
 * 则文档权重 w = p1/(p1+p2)
 * 如果p1+p2=0,文档权重为1
 * 权重低于0.5的关键词会降低整体权重，大于0.5则会提高整体权重
 * 如0.9, 0.8, 0.5, 0.6 经过Bayes计算后权重为0.98，
 * 而0.9, 0.8, 0.5, 0.1 经过计算后权重仅为0.8
 */
class Bayes {

	/**
	 * 获取文章权重
	 * @param $keys 文档中匹配的关键词数组及权重信息
	 * @return  $weight 经过Bayes算法处理过的权重
	 */
	function getWeight($keys) {
		//print_r($keys);
		$p1 = 1;
		$p2 = 1;
		foreach($keys as $key) {
			if( empty($key[1]) ) {
				continue;
			}
			$weight = floatval($key[1]);
			$p1 *= $weight;
			$p2 *= (1- $weight);
		}
		if( ($p1 + $p2) == 0 ) {
			$weight = 1;
			return $weight;
		}
	
		$weight = $p1 / ($p1 + $p2);
		return $weight;
	}

	/**
	 * 获取文章权重
	 * @param $keys 文档中匹配的关键词数组及权重信息
	 * @return  $weight 经过Bayes算法处理过的权重
	 */
	function getWeightByName($keys) {
		//print_r($keys);
		$p1 = 1;
		$p2 = 1;
		foreach($keys as $key) {
			if( empty($key['weight']) ) {
				continue;
			}
			$weight = floatval($key['weight']);
			$p1 *= $weight;
			$p2 *= (1- $weight);
		}
		if( ($p1 + $p2) == 0 ) {
			$weight = 1;
			return $weight;
		}
	
		$weight = $p1 / ($p1 + $p2);
		return $weight;
	}
	
    
}