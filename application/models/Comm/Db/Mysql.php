<?php
/**
 * @author Damon 2013.11.26
 */
class Comm_Db_MysqlModel
{
    private $conn = null;
    private $model = null;

    private $modelRead = '1';
    private $modelWrite = '2';
    private $_startTrans = false;

    private $_transCounter = 0;

    private $_sql = '';

    private $_objTimer = null;

    //private $_objNotify = NULL;

   /**
    * 构造函数，实现mysql连接
    *
    * @param string $dbHost
    * @param string $dbPort
    * @param string $dbUser
    * @param string $dbPwd
    * @param string $dbName
    * @param string $charset
    * @param string $method [w|r]
    * @return void
    */
    
    public function __construct($dbHost, $dbPort, $dbUser, $dbPwd, $dbName, $charset, $method = 'w')
    {
        $this->conn = @mysql_connect($dbHost . ':' . $dbPort, $dbUser, $dbPwd, true);
        if(!$this->conn) {
            //Ekw_DBC::setBlackList();
           // Glo_Log::warning("Error:[mysql connect fail], Detail:['times':'" . time() . "'], mysql_error:[" . mysql_error() . "]");
            throw new Yaf_Exception('mysql connect faild');
        }

        if(!mysql_select_db($dbName, $this->conn)) {
            //Glo_Log::warning("Error:[mysql select db error], Detail:['times':'" . time() . "'], mysql_error[" . mysql_error($this->conn) . "]");
            throw new Yaf_Exception('mysql select db error');
        }

        if(!mysql_set_charset($charset, $this->conn)) {
            //Glo_Log::warning("Error:[mysql set charset error], Detail:['times':'" . time() . "'], mysql_error[" . mysql_error($this->conn) . "]");
            throw new Yaf_Exception('mysql select set charset error');
        }

        if($method == 'r') {
            $this->model = $this->modelRead;
        }
        else if($method== 'w') {
            $this->model = $this->modelWrite;
        }
        else {
            throw new Yaf_Exception('mysql model error');
        }

        //$this->_objNotify = new Tool_Notify();
    }

   /**
    * 执行任何SQL语句(一般情况不建议使用, 可不受读写分离控制)
    *
    * @param string $sql
    * @return resource 
    */
    public function query($sql)
    {
        $this->_objTimer = new Glo_Timer(false, Glo_Timer::PRECISION_MS);
        $this->_objTimer->start();
        $this->dbActionLog($sql);//记录用户的操作记录
        $sql = $this->_sql . $sql;

        $rs = mysql_query($sql, $this->conn);
        if (!$rs) {
            $this->_objTimer->stop();
            $intExecuteTimes = $this->_objTimer->getTotalTime();
            Glo_Log::warning("Error:[mysql_query_error], msg:[ " . mysql_error($this->conn) . "], sql:[({$sql})], execute_times: [{$intExecuteTimes}]");
        }

        return $rs;
    }

    /**
     * 记录数据库行为用的   不记录业务上的需求  新增时注意
     * 
     * @param string $sql
     * 
     * @return resource
     */
    private function query_db_actoin($sql) {
        return mysql_query($sql, $this->conn);
    }

    private function dbActionLog ($sqlrecord = '') {
        //助教操作， 记录写操作日志
        $sqlrecord = strtolower($sqlrecord);
        if (strpos($sqlrecord, 'select') === false && isset($_SESSION['assistant'])) {
            $sqlrecord = htmlspecialchars($sqlrecord , ENT_QUOTES);
            $sql = 'INSERT INTO '. 
                       '`athena_db_actionlog` ' .  
                       '(`users_uid`, `times`, `action_sql`) ' .
                   'VALUES ' .
                      "(%d, %d, '%s')";
            $sql = sprintf($sql, $_SESSION['assistant']['uid'], time(), $sqlrecord);
            $re = $this->query_db_actoin($sql);
        }

        return ;
    }

   /**
    * 执行insert语句
    *
    * @param string $sql
    * @return mixed (false:失败, 成功返回记录条数)
    */
    public function insert($sql,$insert_id = true)
    {
        $this->isWriteModel('insert');
        if($this->query($sql)) {
            if(!$insert_id) return mysql_affected_rows($this->conn);
            return mysql_insert_id($this->conn);
        }

        return false;
    }

   /**
    * 执行replace into语句
    *
    * @param string $sql
    * @return mixed (false:失败, 成功返回记录条数)
    */
    public function replace($sql)
    {
        $this->isWriteModel('replace');

        return $this->update($sql);
    }

   /**
    * 执行update语句
    *
    * @param string $sql
    * @return mixed (false:失败, 成功返回记录条数)
    */
    public function update($sql)
    {
        $this->isWriteModel('update');
        if($this->query($sql)) {
            $intEffectedRows = mysql_affected_rows($this->conn);
            return $intEffectedRows;
            //return mysql_affected_rows();
        }

        return false;
    }

    public function delete($sql)
    {
        $this->isWriteModel('delete');
        if($this->query($sql)) {
            return mysql_affected_rows($this->conn);
        }

        return false;
    }

   /**
    * 执行select语句,多条
    *
    * @param string $sql
    * @return array (与字段名关联的二维数组)
    */
    public function select($sql) 
    {
        $this->isReadModel('select');
        $result = $this->query($sql);
        $rs = array();
        if($result) {
            while($row = mysql_fetch_assoc($result)) {
                array_push($rs, $row);
            }
            mysql_free_result($result);
        }
        return $rs;
    }

   /**
    * 执行select语句,一条
    *
    * @param string $sql
    * @return array
    */
    public function getRow($sql)
    {
        $this->isReadModel('getRow');
        $result = $this->query($sql);
        $rs = array();
        if($result) {
            $rs = @mysql_fetch_assoc($result);
        }
        if($rs) {
            mysql_free_result($result);
        }

        return $rs;
    }

   /**
    * 执行select语句,单条单字段
    *
    * @param string $sql
    * @return array 
    */
    public function getOne($sql)
    {
        $this->isReadModel('getOne');
        $result = $this->query($sql);
        if ($rs = @mysql_fetch_array($result, MYSQL_NUM)) {
            return $rs[0];
        }

        return NULL;
    }

   /**
    * 检查此次连接是否为读库连接
    *
    * @param string $funcname
    * @return void
    */
    private function isReadModel($funcname)
    {
        /*
        if(!($this->model == $this->modelRead)) {
            throw new Comm_Exception_Program("function {$funcname} can't work in model " . $this->model);
        }
        */
    }

   /**
    * 检查此次连接是否为写库连接
    *
    * @param string $funcname
    * @return void
    */
    private function isWriteModel($funcname)
    {
        /*
        if(!($this->model == $this->modelWrite)) {
            throw new Comm_Exception_Program("function {$funcname} can't work in model " . self::$model);
        }
        */
    }

   /**
    * 事务开始
    *
    *
    */
    public function startTrans()
    {
        if ($this->_startTrans === false){
            //Glo_Log::debug('------- start trans --------');
            $this->isWriteModel('BEGIN');
            $this->query('BEGIN');
            $this->_startTrans = true;
        }
    }

   /**
    * 事务回滚
    */
    public function rollback()
    {
        if ($this->_startTrans === true){
            //Glo_Log::debug('------ rollback ----- ');
            $this->isWriteModel('rollback');
            $this->query('ROLLBACK');
            $this->_startTrans = false;
        }
    }

   /**
    * 事务提交
    */
    public function commit()
    {
        if ($this->_startTrans === true){
            //Glo_Log::debug('------- commit --------');
            $this->isWriteModel('COMMIT');
            $this->query('COMMIT');
            $this->_startTrans = false;
        }
    }

    /**
    * @Brief 新的事务处理机制  [采用计数器方式来提交事务]
    *
    * @Param string $strMethod [BEGIN;ROLLBACK;COMMIT]
    *
    * @Returns void
    *
    * @author damon <cuizhongzhi@moyi365.com>
    *
    * @Date 2014-07-27
    */
    public function trans($strMethod) {
        $strMethod = strtoupper($strMethod);

        switch($strMethod) {
            case 'BEGIN':
                $this->_transCounter++;
                if(1 === $this->_transCounter) {
                    $this->query('BEGIN');
                }
                break;
            case 'ROLLBACK':
                if($this->_transCounter > 0) {
                    $this->_transCounter--;
                    $this->query('ROLLBACK');
                }
                break;
            case 'COMMIT':
                if($this->_transCounter > 0) {
                    $this->_transCounter--;
                    if(0 === $this->_transCounter) {
                        $this->query('COMMIT');
                        //$this->_objNotify->execute();
                    }
                }
                break;
        }
    }

    public function getLastSql() {
        return $this->_sql;
    }
    public function setProxy($master = '') {
        $this->_sql = $master;
    }

    function __destruct()
    {
        @mysql_close($this->conn);
    }
}

