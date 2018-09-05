<?php
/**
* @file Redis.php
* @synopsis redis 连接封装
* @author wangwenyue, <wangwenyue@moyi365.com>
* @version 1.0
* @date 2015-11-9
*/
class Comm_Db_Redis {
    //redis 对象   
    private $objRedis = null;
    //redis 配置
    private $arrRedisConf = array();
    //连接失败后重连次数
    private $call_redis_times = 0;

    public $pong = false;

    public function __construct($arrRedisConf){
        if(!$arrRedisConf){
            Glo_Log::warning("Redis conn need conf ,Detail['time':'".time()."';file: ".__FILE__.";line:".__LINE__."]");
            //throw new Yaf_Exception('Redis connect failed');
            $this -> pong = false;
        }   
        else {
            $this -> arrRedisConf = $arrRedisConf;
            $this -> objRedis = new Redis();
            $boolConn = $this -> initRedis();
            if( $this->objRedis && $boolConn){
                $this -> pong = true;
            }else{
                $this -> pong = false;
            }
        }
    }   
    public function __call($strMothod,$arrParams){
        try{
            return call_user_func_array(array($this->objRedis,$strMothod),$arrParams);
        }catch(RedisException $e){
            Glo_Log::warning("Redis func defined fail,Detail :[func $strMothod ;time:'".time()."'][info:".json_encode($arrParams)."] [Exception: " . json_encode($e) . "]");
            return false;
        }
    }
    //实现重连机制
    public function initRedis(){
        $intTime = 0;
        //ini_set('default_socket_timeout', -1);

        //$boolConn = $this -> objRedis -> connect($this->arrRedisConf['host'],$this->arrRedisConf['port'], $this-> arrRedisConf['timeout']);
        $boolRetry = false;
        while($intTime <= $this->call_redis_times){
            $intTime ++;
            $boolRetry = $this -> objRedis ->connect($this->arrRedisConf['host'],$this->arrRedisConf['port'],$this->arrRedisConf['timeout']);
            if($boolRetry){
                return $this->authRedis();
            } else {
                Glo_Log::warning("Redis conn {$this->call_redis_times} times fail ,Detail['time':'".time()."';file: ".__FILE__.";line:".__LINE__."]");
                return false;
            }
        }
        return false;

    }

    public function authRedis(){
        try{
            $boolAuth = $this -> objRedis -> auth($this -> arrRedisConf['password']);
            if(!$boolAuth){
                Glo_Log::warning("Redis auth fail ,Detail['time':'".time()."';file: ".__FILE__.";line:".__LINE__."]");
            }
        
        }catch( RedisException $e){
            Glo_Log::warning("Redis auth exception ,Detail['time':'".time()."';file: ".__FILE__.";line:".__LINE__.";Exception:" . json_encode($e) . "]");
            return false;
        }
        return $boolAuth;
    }

    public function ping(){
        return $this -> objRedis -> ping();
    }
    
    /**
     * @synopsis 写入单个
     *
     * @param $strKey   key值
     * @param $strValue value 值
     * @param $intTime  key有限时间
     *
     * @return
     */
    public function set($strKey, $strValue, $intTime = 0){
        if(is_array($strValue)){
            $strValue = json_encode($strValue);
        }
        if($intTime){
            
            try{
                $res = $this->objRedis->setex($strKey, $intTime, $strValue);
                if(!$res){
                    Glo_Log::warning("Redis setex fail,Detail :[time:'".time()."'][key:".$strKey.",res:".json_encode($res)."] ");
                }
                return $res;
            }catch(RedisException $e){
            
                Glo_Log::warning("Redis setex exception,Detail :[time:'".time()."'][key:$strKey,value:$strValue,time:".$intTime."] [Exception: " . json_encode($e) . "]");
                return false;
            }
        }else{
            try{
                return $this->objRedis->set($strKey, $strValue);
            }catch(RedisException $e){
                Glo_Log::warning("Redis set exception,Detail :[time:'".time()."'][key:$strKey,value:$strValue]  [Exception: " . json_encode($e) . "]");
                return false;
            }
        }
    }
    
    /**
     * @synopsis 写入多个
     *
     * @param $arrInfo  array('key'=>'value')
     *
     * @return
     */
    public function sets($arrInfo,  $intTime = 0){
        if(!is_array($arrInfo)){
            Glo_Log::warning("Redis sets is not array , Detail : [time : ".time()."][info:".json_encode($arrInfo)."]");    
            return false;
        }
        if($intTime){
            foreach($arrInfo as $k => $v){
                if(is_array($v)){
                    $v = json_encode($v);
                }
                $this->set($k, $v, $intTime);
            }
        }else{
            try{
                return $this->objRedis->mset($arrInfo);
            }catch(RedisException $e){
                Glo_Log::warning("Redis sets exception,Detail :[time:'".time()."'][info:".json_encode($arrInfo)."] [Exception: " . json_encode($e) . "]");
                return false;
            }
        }
    }
    public function get($strKey){
        try{
            return $this->objRedis->get($strKey);
        }catch( Exception $e){
            Glo_Log::warning("Redis get exception ,Detail :[time:'".time()."'] [key:{$strKey}] [Exception: ". json_encode($e) . "]");
            return false;
        }
    }
    public function gets($arrKey){
        try{
            $res = $this->objRedis->mget($arrKey);
            if(!$res){
                Glo_Log::warning("Redis gets fail,Detail :[time:'".time()."'][info:res:".json_encode($res).'key:'.json_encode($arrKey)."] ");
            }
            return $res;
        }catch( Exception $e){
            Glo_Log::warning("Redis gets exception ,Detail :[time:'".time()."'][info:".json_encode($arrKey)."] [Exception: " . json_encode($e) . "]");
            return false;
        }
    }
    /**
     * @synopsis 设置有效时间
     *
     * @param $arrInfo  array('key'=>'value')
     *
     * @return
     */
    
    public function expireat($strKey, $strTime){
        try{
            return $this-> objRedis-> expireat($strKey, $strTime);
        }catch( Exception $e){
            Glo_Log::warning("Redis expireat exception ,Detail :[time:'".time()."'] [key: {$strKey}, time: {$strTime}] [Exception: " . json_encode($e) . "]");
            return false;
        }
    }
    public function close(){
        $this->pong && $this -> objRedis->close();
    }
    /**
     * @synopsis 判断key是否存在
     *
     * @param $strKey key
     *
     * @return boolean 
     */
    public function exists($strKey) {
        return $this->objRedis->exists($strKey);
    }
    /**
     * @synopsis 将key所储存的值加上增量increment。
     *
     * @param $strKey key
     * @param $intVal 默认的increment为1
     *
     * @return int 
     */
    public function incrby($strKey, $intVal = 1) {
        return $this->objRedis->incrby($strKey, $intVal);
    }
    /**
     * @synopsis 删除某个值
     *
     * @param $params  array('key1','key2')|| 'key1'
     *
     * @return 成功删除的个数
     */
    public function del($params){
        return $this->objRedis -> del($params);
    }
    public function callRedis($strMothod,$arrParams){
        try{
            return call_user_func_array(array($this->objRedis,$strMothod),$arrParams);
        }catch(RedisException $e){
print_r($e);
            Glo_Log::warning("Redis func defined fail,Detail :[func $strMothod ;time:'".time()."'][info:".json_encode($arrParams)."] [Exception: " . json_encode($e) . "]");
            return false;
        }
    }
    public function rEval($commend, $argv, $argc) {
        try{
            return $this->objRedis->eval($commend, $argv, $argc);
        }catch(RedisException $e){
            Glo_Log::warning("Redis eval fail,Detail :[commend:$commend; argv:".json_encode($argv)."; argc:$argc][Exception:".json_encode($e)."] ");
            return false;
        }
        
    }
    public function getLastError(){
        return $this->objRedis -> getLastError();
    }
    public function script($strCommend){
        return $this->objRedis -> script('load', $strCommend);
    }
    public function evalSha($sha, $argv, $argc){
        try{
            return $this->objRedis->evalSha($sha, $argv, $argc);
        }catch(RedisException $e){
            Glo_Log::warning("Redis evalSha fail,Detail :[commend:$sha; argv:".json_encode($argv)."; argc:$argc][Exception:".json_encode($e)."] ");
            return false;
        }
    }

    public function __destruct() {
        $this->close();
    }
}


