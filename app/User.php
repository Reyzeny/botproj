<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
   

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $guarded = [];

    public function user_exists($userId) {
        $user = $this->where('user_id', $userId)->first();
        return $user!=null;
    }

    public function email_exists($user_id) {
        $result = DB::select('select email from users where user_id=?', [$user_id]);
        
        if ($result==null || empty($result)) {
            return null;
        }
        return $result[0]->email!=null || !empty($result[0]->email);
    }

    public function firstname_exists($user_id) {
        $result = DB::select('select firstname from users where user_id=?', [$user_id]);
        if ($result==null || empty($result)) {
            return null;
        }
        return $result[0]->firstname!=null || !empty($result[0]->firstname);
    }

    public function lastname_exists($user_id) {
        $result = DB::select('select lastname from users where user_id=?', [$user_id]);
        if ($result==null || empty($result)) {
            return null;
        }
        return $result[0]->lastname!=null || !empty($result[0]->lastname);
    }

    public function create_user($user_id) {
        DB::insert('insert into users(user_id) values(?)', [$user_id]);
    }

    public function get_name($user_id) {
        $result=DB::select('select firstname from users where user_id=?', [$user_id]);
        //dd($result);
        $firstname = $result[0]->firstname;
        if (empty($firstname)) {
            $firstname = "friend";
        }
        return $firstname;
    }

    public function getByUniqueId($userId) {
        $result = DB::select('select user_id from users where user_id=:id', ['id'=>$userId]);
        if ($result==null || empty($result)) {
            return null;
        }
        return $result[0];
    }
}
