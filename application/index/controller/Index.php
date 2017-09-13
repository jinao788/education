<?php
namespace app\index\controller;

use app\index\controller\Base;

class Index extends Base
{
    public function index()
    {
    	$this -> isLogin();  //判断用户是否登录
        
        return $this->view->fetch();  //view不带括号
    }
}
