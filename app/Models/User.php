<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function gravatar($size = '100')
    {
        $hash = md5(strtolower(trim($this->attributes['email'])));
        return "http://www.gravatar.com/avatar/$hash?s=$size";
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $user->activation_token = str_random(30);
        });
    }

    public function statuses()
    {
        return $this->hasMany(Status::class);
    } 

    //动态流原型 --- 用户模型
    public function feed()
    {
        /*
        $user->followings与 $user->followings()是不同的，
        $user->followings 返回的是Eloquent:集合    返回的是数据集合
        $user->followings() 返回的是数据库请求构造器   返回的是数据库查询语句
        $user->followings  ==  $user->followings()->get()
        */
        $user_ids = $this->followings->pluck('id')->toArray(); //将当前用户的id加入到user_ids数组中
        array_push($user_ids, $this->id);
        return Status::whereIn('user_id', $user_ids)
                              ->with('user')
                              ->orderBy('created_at', 'desc');
    }

    public function followers()
    {
        return $this->belongsToMany(User::Class, 'followers', 'user_id', 'follower_id');
    }

    public function followings()
    {
        return $this->belongsToMany(User::Class, 'followers', 'follower_id', 'user_id');
    }
    //其他页面可以通过 $user->followers() 来获取粉丝关系列表
    //其他页面可以通过 $user->followings() 来获取关注人列表

    public function follow($user_ids)
    {
        if ( ! is_array($user_ids)) {
            $user_ids = compact('user_ids');
        }
        $this->followings()->sync($user_ids, false);
    }

    public function unfollow($user_ids)
    {
        if ( ! is_array($user_ids)) {
            $user_ids = compact('user_ids');
        }
        $this->followings()->detach($user_ids);
    }

    //判断用户B是否包含在用户A的关注人列表上，(判断当前登录的用户A是否关注了用户B)
    public function isFollowing($user_id)
    {
        return $this->followings->contains($user_id);
    }
}
