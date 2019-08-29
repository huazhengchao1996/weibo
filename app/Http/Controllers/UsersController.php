<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Mail;
use Auth;

class UsersController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth',[
            'except' => ['show', 'create', 'store', 'index', 'confirmEmail']
        ]);

        $this->middleware('guest',[
           'only' => ['create']
        ]);
    }

    //用户列表页面
    public function index()
    {
        $users = User::paginate(10);
        return view('users.index', compact('users'));
    }

    //用户注册界面
    public function create()
    {
        return view('users.create');
    }

    //用户显示界面
    public function show(User $user)
    {
        return view('users.show',compact('user'));
    }

    //用户注册逻辑
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:50',
            'email' => 'required|email|unique:users|max:255',
            'password' => 'required|confirmed|min:6'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $this->sendEmailConfirmationTo($user);
        session()->flash('success','验证邮件已发送到您的注册邮箱上，请注意查收。');
        return redirect('/');
    }

    //邮件发送
    protected function sendEmailConfirmationTo($user)
    {
        $view = 'emails.confirm';
        $data = compact('user');
        $to = $user->email;
        $subject = "感谢注册 Weibo 应用！请确认您的邮箱。";

        Mail::send($view, $data, function ($message) use ($to, $subject) {
            $message->to($to)->subject($subject);
        });
    }

    //用户修改界面
    public function edit(User $user)
    {
        $this->authorize('update', $user);
        return view('users.edit', compact('user'));
    }

    //用户修改逻辑
    public function update(User $user, Request $request)
    {
        $this->authorize('update', $user);
        $this->validate($request,[
            'name' => 'required|max:50',
            'password' => 'nullable|confirmed|min:6'
        ]);

        $data = [];
        $data['name'] = $request->name;
        if($request->password){
            $data['password'] = bcrypt($request->password);
        }
        $user->update($data);

        session()->flash('success','个人资料更新成功！');

        return redirect()->route('users.show',$user);

    }

    //删除用户
    public function destroy(User $user)
    {
        $this->authorize('destroy', $user);
        $user->delete();
        session()->flash('success','成功删除用户');
        return back();
    }

    //激活邮箱
    public function confirmEmail($token)
    {
        $user = User::where('activation_token',$token)->firstOrfail();

        $user->activated = true;
        $user->activation_token = null;
        $user->save();

        Auth::login($user);
        session()->flash('success','恭喜您，激活成功！');
        return redirect()->route('users.show',[$user]);
    }
}
