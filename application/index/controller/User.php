<?php

namespace app\index\controller;

use app\index\controller\Base;
use think\Request;
use app\index\model\User as UserModel;
use think\Session;

class User extends Base
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function login()
    {
        $this -> alreadyLogin();
        return $this->view->fetch();
    }


    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function checkLogin(Request $request)
    {
        $status = 0;
        $result = '';
        $data = $request->param();
        //创建验证规则
        $rule =[
            'name|用户名' =>'require',
            'password|密码'=>'require',
            'verify|验证码'=>'require|captcha',  
        ];

        $msg =[
            'name'=>['require'=>'用户名不能为空,请检查'],
            'password'=>['require'=>'密码不能为空,请检查'],
            'verify'=>[
            'require'=>'验证码不能为空,请检查',
            'captcha'=>'验证码错误,请检查'
            ],
        ];

        $result =$this->validate($data,$rule,$msg);

        if($result === true){
            $map=[
            'name'=>$data['name'],
            'password'=>md5($data['password']),
            ];


            $user = UserModel::get($map);
            if($user == null){
                $result="用户名或者密码错误";
            }else{
                $status = 1;
                $result="验证通过";

                $user -> setInc(('login_count'));
                $user -> save(['login_time'=> time()]);

                Session::set ('user_id',$user->id);
                Session::set ('user_name',$user->name);
                Session::set ('login_count',$user->login_count); //重点 $admin->toArray()
                Session::set ('login_time',$user->login_time);



            }
        }


        return ['status' =>$status ,'message'=>$result,'data'=>$data];
    }



    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function logout()
    {   //这个是上次退出时间
        //UserModel::update(['login_time'=>time()],['id'=> Session::get('user_id')]);
        Session::delete('user_id');
        //Session::delete('user_info');
        $this -> success('注销登陆,正在返回',url('user/login'));
    }


     public function  adminList()
    {
        // $this -> view -> assign('title', '管理员列表');
        // $this -> view -> assign('keywords', '');
        // $this -> view -> assign('desc', '');
//统计共有数据的
        $this -> view -> count = UserModel::count();

        //判断当前是不是admin用户
        //先通过session获取到用户登陆名
        $userName = Session::get('user_name');
        if ($userName == 'admin') {
            $list = UserModel::all();  //admin用户可以查看所有记录,数据要经过模型获取器处理
            $this -> view -> assign('list', $list);
            return $this -> view -> fetch('admin_list');
        } else {
            //为了共用列表模板,使用了all(),其实这里用get()符合逻辑,但有时也要变通 但消耗资源,不如分开模板
            //非admin只能看自己信息,数据要经过模型获取器处理
            $list_one = UserModel::get(['name'=>$userName]);
            $this -> view -> assign('list_one', $list_one);
            return $this -> view -> fetch('admin_list_one');
        }
                                        //为什么非得分开赋值渲染模板,不能共用一个list变量

        // $this -> view -> assign('list', $list);
        // //$this -> view -> assign('list_one', $list_one);
        // // //渲染管理员列表模板
        // return $this -> view -> fetch('admin_list');
        // return $this -> view -> fetch('admin_list_one');
    }


//管理员状态变更
    public function setStatus(Request $request)
    {
        $user_id = $request -> param('id');
        $result = UserModel::get($user_id);
        if($result->getData('status') == 1) {
            UserModel::update(['status'=>0],['id'=>$user_id]);
        } else {
            UserModel::update(['status'=>1],['id'=>$user_id]);
        }
    }

    //渲染编辑管理员界面
    public function adminEdit(Request $request)
    {
        $user_id = $request -> param('id');
        $result = UserModel::get($user_id);
        // $this->view->assign('title','编辑管理员信息');
        // $this->view->assign('keywords','');
        // $this->view->assign('desc','');
        $this->view->assign('user_info',$result->getData());
        return $this->view->fetch('admin_edit');
    }


    //更新数据操作
    public function editUser(Request $request)
    {
        //获取表单返回的数据
//        $data = $request -> param();
        $param = $request -> param();


        //去掉表单中为空的数据,即没有修改的内容 可以有效防止更改为空
        foreach ($param as $key => $value ){
            if  ($value !=''){
                $data[$key] = $value;
            }
        }

       
        //
//dump($result);die;  返回是一个对象

        //如果是admin用户,更新当前session中用户信息user_info中的角色role,供页面调用
        if (Session::get('user_info.name') == 'admin') {
            //Session::set('user_info.role', $param['role']);
        }

       
        $status = 1;
        $message = '修改成功';

        $rule = [
            'name|用户名' => "require|unique:user",
            'password|密码' => "min:3|max:10",
            'email|邮箱' => 'require|email|unique:user'
        ];

        // $msg = [
        //     'name'=>['require'=>'用户名不能为空'],
        //     'name'=>['unique'=>'用户名不能重复'],
        //     'password'=>['require'=>'密码不能为空'],
        //     'password'=>['min'=>'密码不能少于3位'],
        //     'password'=>['max'=>'密码不能大于10位'],
        //     'email'=>['require'=>'邮箱不能少于3位'],
        //     'email'=>['unique'=>'邮箱不能重复'],

        // ];

        $result = $this -> validate($data, $rule); //返回的就是提示信息了
        //dump($result);die;

        if ($result === true) {
            $user = UserModel::update($data, $data['id']);
            if ($user === null) {
                $status = 0;
                $message = '修改失败~~';
            }
        }else{
                $status = 0;
                //$message = '格式验证未通过~~';
                $message = $result;
            }

        return ['status'=>$status, 'message'=>$message];

    }


   

    //删除操作
    public function deleteUser(Request $request)
    {
        $user_id = $request -> param('id');
        //UserModel::update(['delete_time'=>time()],['id'=> $user_id]);
        UserModel::destroy($user_id);
    }


     //恢复删除操作
    public function adminReback(Request $request)
    {
        
        if($user_id = $request -> param('id')){

             //dump($user_id);die;
            $user= UserModel::update(['delete_time'=>NULL],['id'=>$user_id]);
            $user= UserModel::get($user_id);
            //dump($user);die;
            if ($user === null) {
                $status = 0;
                $message = '恢复失败~~';
            }else{
                $status = 1;
                $message = '恢复成功';
            }
        
               

            return ['status'=>$status, 'message'=>$message];
        }

       

        return $this->view->fetch('admin_reback');
        
    }
    
     //添加操作的界面
        public function  adminAdd()
        {
            // $this->view->assign('title','添加管理员');
            // $this->view->assign('keywords','');
            // $this->view->assign('desc','');
            return $this->view->fetch('admin_add');
        }

    //添加 检测用户名是否可用
    public function checkUserName(Request $request)
    {
        $userName = trim($request -> param('name'));
        $status = 1;
        $message = '用户名可用';
        if (UserModel::get(['name'=> $userName])) {
            //如果在表中查询到该用户名
            $status = 0;
            $message = '用户名重复,请重新输入~~';
        }elseif(empty($userName)){
             //如果在表中查询到该用户名
            $status = 0;
            $message = '用户名不得为空,请重新输入~~';
        }

       
        return ['status'=>$status, 'message'=>$message];
    }

    //检测用户邮箱是否可用
    public function checkUserEmail(Request $request)
    {
        $userEmail = trim($request -> param('email'));
        $status = 1;
        $message = '邮箱未被占用';
        if (UserModel::get(['email'=> $userEmail])) {
            //查询表中找到了该邮箱,修改返回值
            $status = 0;
            $message = '邮箱重复,请重新输入~~';
        }elseif(empty($userEmail)){
             //如果在表中查询到该用户名
            $status = 0;
            $message = '邮箱不得为空,请重新输入~~';
        }
        return ['status'=>$status, 'message'=>$message];
    }

    //添加操作
    public function addUser(Request $request)
    {
        $data = $request -> param();
        $status = 1;
        $message = '添加成功';

        $rule = [
            'name|用户名' => "require|unique:user",
            'password|密码' => "require|min:3|max:10",
            'email|邮箱' => 'require|email|unique:user'
        ];

        // $msg = [
        //     'name'=>['require'=>'用户名不能为空'],
        //     'name'=>['unique'=>'用户名不能重复'],
        //     'password'=>['require'=>'密码不能为空'],
        //     'password'=>['min'=>'密码不能少于3位'],
        //     'password'=>['max'=>'密码不能大于10位'],
        //     'email'=>['require'=>'邮箱不能少于3位'],
        //     'email'=>['unique'=>'邮箱不能重复'],

        // ];

        $result = $this -> validate($data, $rule); //返回的就是提示信息了
        //dump($result);die;

        if ($result === true) {
            $user= UserModel::create($request->param());
            if ($user === null) {
                $status = 0;
                $message = '添加失败~~';
            }
        }else{
                $status = 0;
                //$message = '格式验证未通过~~';
                $message = $result;
            }

        return ['status'=>$status, 'message'=>$message];
    }
        

        



////////////////////////////////////////////////////////
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        //
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        //
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        //
    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        //
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //
    }
}
