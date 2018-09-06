<?php
/**
 * @name IndexController
 * @author powermanlc
 * @desc 默认控制器
 * @see http://www.php.net/manual/en/class.yaf-controller-abstract.php
 */
class IndexController extends Yaf_Controller_Abstract {

	/** 
     * 默认动作
     * Yaf支持直接把Yaf_Request_Abstract::getParam()得到的同名参数作为Action的形参
     * 对于如下的例子, 当访问http://yourhost/yaftest/index/index/index/name/powermanlc 的时候, 你就会发现不同
     */
	public function indexAction($name = "Stranger") {
		//1. fetch query
		$get = $this->getRequest()->getQuery("get", "default value");
        //$file_path = "/home/www/yaftest/conf/db.ini";
        //$obj_conf = new Yaf_Config_Ini ($file_path, $section);
        //$conf = Yaf_Application::app()->getConfig();
        //$this->sqlConn();
        $this->sortArray();
//$objConf1 = Yaf_Registry::get('db')->toArray();
//$objConf2 = Yaf_Registry::get('config');
//$objConf3 = Yaf_Registry::get('db');
  //  var_dump($objConf1['test']);die;
        //var_dump($obj_conf, $conf, $objConf);
       // var_dump($objConf1, $objConf2, $objConf3);

		//2. fetch model
		$model = new SampleModel();

		//3. assign
		$this->getView()->assign("content", $model->selectSample());
		$this->getView()->assign("name", $name);

		//4. render by Yaf, 如果这里返回FALSE, Yaf将不会调用自动视图引擎Render模板
        return TRUE;
	}
    private function sqlConn() {
        $objConf1 = Yaf_Registry::get('db')->toArray();
        
        $db = Comm_Db_ConnModel::getDb();
        return $db;
    }

    private function sortArray() {
        $arr = array("aa" => 2, "dd" => 2, "bb" => 2);
        asort($arr);
var_dump($arr);die;
    }
}

