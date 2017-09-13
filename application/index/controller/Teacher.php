<?php

namespace app\index\controller;
use app\index\model\Teacher as TeacherModel;
use think\Request;


class Teacher extends Base
{
    //教师列表
    public function  teacherList()
    {
        //获取所有教师表teacher数据
        $teacher = TeacherModel::all();

        //获取记录数量
        $count = TeacherModel::count();
        //遍历teacher表
        foreach ($teacher as $value){
            $data = [
                'id' => $value->id,  //主键
                'name' => $value->name,  //姓名
                'degree' => $value->degree,  //学历
                'school' => $value->school,  //毕业学校
                'mobile' => $value->mobile,  //手机号
                'hiredate' => $value->hiredate,  //入职时间
                'status' => $value->status,  //当前启用状态
                //用关联方法grade属性方式访问grade表中数据
                'grade' => isset($value->grade->name)? $value->grade->name : '<span style="color:red;">暂未分配</span>',
            ];
            //每次关联查询结果,保存到数组   $teacher中
            $teacherList[] = $data;
        }

        $this -> view -> assign('teacherList', $teacherList);
        $this -> view -> assign('count', $count);

        //设置当前页面的seo模板变量
        // $this->view->assign('title','教师列表');
        // $this->view->assign('keywords','');
        // $this->view->assign('desc','');

        return $this -> view -> fetch('teacher_list');
    }

    //教师状态变更
    public function setStatus(Request $request)
    {
        $teacher_id = $request -> param('id');
        $result = TeacherModel::get($teacher_id);
        if($result->getData('status') == 1) {
            TeacherModel::update(['status'=>0],['id'=>$teacher_id]);
        } else {
            TeacherModel::update(['status'=>1],['id'=>$teacher_id]);
        }
    }

    //渲染教师编辑界面
    public function teacherEdit(Request $request)
    {
        $teacher_id = $request -> param('id');
        $result = TeacherModel::get($teacher_id);

        //设置当前页面的seo模板变量
        // $this->view->assign('title','编辑教师信息');
        // $this->view->assign('keywords','');
        // $this->view->assign('desc','');

        //给当前教师编辑页面模板赋值
        $this->view->assign('teacher_info',$result);

        //将班级表中所有数据赋值给当前模板
        $this->view->assign('gradeList',\app\index\model\Grade::all());

        //渲染出当前的编辑模板
        return $this->view->fetch('teacher_edit');
    }

    //教师更新
    public function doEdit(Request $request)
    {
        //从提交表单中排除关联字段teacher字段
        $data = $request -> except('grade');

        $status = 1;
        $message = '修改成功';

        $rule = [
            'name|姓名' => "require|unique:grade",
            'mobile|手机' => "require",
            'school|毕业院校' => 'require',
            'hiredate|入职时间' => 'require',
        ];

       
        $result = $this -> validate($data, $rule); //返回的就是提示信息了
        //dump($result);die;

        if ($result === true) {
           
           $user = TeacherModel::update($data, ['id'=>$data['id']]);

            if ($user === null) {
                $status = 0;
                $message = '修改失败~~';
            }
        }else{
                $status = 0;
                $message = $result;
            }

        return ['status'=>$status, 'message'=>$message];

    }

    //渲染教师添加界面
    public function teacherAdd()
    {
        // $this->view->assign('title','添加教师');
        // $this->view->assign('keywords','');
        // $this->view->assign('desc','');

        //将班级表中所有数据赋值给当前模板
        $this->view->assign('gradeList',\app\index\model\Grade::all());

        return $this->view->fetch('teacher_add');
    }

    //添加教师
    public function doAdd(Request $request)
    {
        //从提交表单中获取数据
        $data = $request -> param();

        $status = 1;
        $message = '添加成功';

        $rule = [
            'name|姓名' => "require|unique:grade",
            'mobile|手机' => "require",
            'school|毕业院校' => 'require',
            'hiredate|入职时间' => 'require',
        ];

       
        $result = $this -> validate($data, $rule); //返回的就是提示信息了
        //dump($result);die;

        if ($result === true) {
           
           $user =  TeacherModel::create($data);

            if ($user === null) {
                $status = 0;
                $message = '添加失败~~';
            }
        }else{
                $status = 0;
                $message = $result;
            }

        return ['status'=>$status, 'message'=>$message];

    }

    //软删除操作
    public function deleteTeacher(Request $request)
    {
        $teacher_id = $request -> param('id');
        //TeacherModel::update(['is_delete'=>1],['id'=> $teacher_id]);
        TeacherModel::destroy($teacher_id);

    }

    //恢复删除操作
    public function unDelete()
    {
        TeacherModel::update(['delete_time'=>NULL],['is_delete'=>1]); //火狐无效
        
    }

}