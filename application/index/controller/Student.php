<?php
namespace app\index\controller;

use app\index\model\Student as StudentModel;
use think\Request;
class Student extends Base
{
    //渲染学生信息列表
    public function  studentList()
    {
        //获取所有学生数据
        $studentList = StudentModel::paginate(5);

        //获取记录数量
        $count = StudentModel::count();

        //给结果集对象数组中的每个模板对象添加班级关联数据,非常重要
        foreach ($studentList as $value) {
            $value -> grade =isset( $value -> grade -> name)? $value -> grade -> name : '<span style="color:red;">暂未分配</span>';
        }

        $this -> view -> assign(['studentList'=> $studentList,'count'=> $count]);
       

        return $this -> view -> fetch('student_list');
    }

    //学生状态变更
    public function setStatus(Request $request)
    {
        $student_id = $request -> param('id');
        $result = StudentModel::get($student_id);

        if($result->getData('status') == 1) {
            StudentModel::update(['status'=>0],['id'=>$student_id]);
        } else {
            StudentModel::update(['status'=>1],['id'=>$student_id]);
        }
    }

    //渲染学生编辑界面
    public function studentEdit(Request $request)
    {
        $student_id = $request -> param('id');
        $result = StudentModel::get($student_id);
        //获取关联表:grade数据
        //$result -> grade = $result -> grade->name;
        $result -> grade = isset( $result -> grade -> name)? $result -> grade -> name : '';



        // $this->view->assign('title','编辑班级');
        // $this->view->assign('keywords','');
        // $this->view->assign('desc','');

        $this->view->assign(['student_info'=>$result,'gradeList'=>\app\index\model\Grade::all()]);
       
        //将班级表中所有数据赋值给当前模板
        return $this->view->fetch('student_edit');
    }

    //更新学生信息
    public function doEdit(Request $request)
    {
        //获取前端提交过来的表单数据,此处可以修改班级信息,请不要过滤过grade字段
        $data = $request -> param();

        $status = 1;
        $message = '修改成功';

        $rule = [
            'name|姓名' => "require|unique:grade",
            'age|年龄' => "require",
            'mobile|手机' => 'require',
            'email|邮箱' => 'require',
            'start_time|入学时间' => 'require',
        ];

       
        $result = $this -> validate($data, $rule); //返回的就是提示信息了
        //dump($result);die;

        if ($result === true) {
            //$user = UserModel::update($data, $data['id']);
            $user = StudentModel::update($data,['id'=>$data['id']]);

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

    //渲染学生添加界面
    public function studentAdd()
    {
        // $this->view->assign('title','添加学生');
        // $this->view->assign('keywords','');
        // $this->view->assign('desc','');

        //将班级表中所有数据赋值给当前模板
        $this->view->assign('gradeList',\app\index\model\Grade::all());

        return $this->view->fetch('student_add');
    }

    //添加学生到表中
    public function doAdd(Request $request)
    {
        //从提交表单中获取数据
        $data = $request -> param();

        //新增学生记录
        $result = StudentModel::create($data);

        //设置返回数据
        $status = 0;
        $message = '添加失败,请检查';

        //检测更新结果,将结果返回给grade_edit模板中的ajax提交回调处理
        if (true == $result) {
            $status = 1;
            $message = '恭喜, 添加成功~~';
        }
        return ['status'=>$status, 'message'=>$message];
    }

    //软删除操作
    public function deleteStudent(Request $request)
    {
        $user_id = $request -> param('id');
        StudentModel::update(['is_delete'=>1],['id'=> $user_id]);
        StudentModel::destroy($user_id);

    }

    //恢复删除操作
    public function unDelete()
    {
        StudentModel::update(['delete_time'=>NULL],['is_delete'=>1]);
    }
}