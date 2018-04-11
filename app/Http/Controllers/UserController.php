<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Requests;
use Auth;
use Mail;

class UserController extends Controller
{
    public function __construct(){
        $this->middleware('auth', [
            'except' => ['show', 'create', 'store', 'index', 'confirmEmail']
        ]);

        $this->middleware('guest', [
            'only' => ['create']
        ]);
    }

    public function index(){
        $users = User::paginate(10);
        return view('users.index', compact('users'));
    }

    public function create(){
        return view('users.create');
    }

    //欢迎也
    public function show(User $user){
        return view('users.show', compact('user'));
    }

    //创建
    public function store(Request $request){
        $this->validate($request, [
            'name' => 'required|max:50',
            'email' => 'required|email|unique:users|max:250',
            'password' => 'required|confirmed|min:6',
            ]);
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $this->sendEmailConfirmationTo($user);

        Auth::login($user);
        session()->flash('success', '验证邮件已发送到你的注册邮箱上，请注意查收。');
        return redirect('/');
    }

    //编辑
    public function edit(User $user){
        $this->authorize('update', $user);
        return view('users.edit', compact('user'));
    }

    //更新
    public function update(User $user, Request $request){
        $this->validate($request, [
            'name' => 'required|max:5',
            'password' => 'required|confirmed|min:6',
        ]);

        $this->authorize('update', $user);

        $date = [];
        $date['name'] = $request->name;
        if ($request->password) {
            $data['password'] = bcrypt($request->password);
        }
        $user->update($date);

        return redirect()->route('users.show', $user->id);
    }

    //删除
    public function destroy(User $user){
        $this->authorize('destroy', $user);
        $user->delete();
        session()->flash('success', '成功删除用户！');
        return back();
    }

    //确认邮箱
    public function confirmEmail($token){
        $user = User::where('activation_token', $token)->firstOrFail();

        $user->activated = true;
        $user->activation_token = null;
        $user->save();

        Auth::login($user);
        session()->flash('success', '恭喜你，激活成功！');
        return redirect()->route('users.show', [$user]);
    }

    //发送邮件
    protected function sendEmailConfirmationTo($user){
        $view = 'emails.confirm';
        $data = compact('user');
        $from = '932921796@qq.com';
        $name = 'bogiang';
        $to = $user->email;
        $subject = '感谢注册 bogiang 应用！请确认你的邮箱。';

        Mail::send($view, $data, function($message) use ($from, $name, $to ,$subject){
            $message->from($from, $name)->to($to)->subject($subject);
        });
    }
}
?>