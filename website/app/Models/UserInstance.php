<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserInstance extends Model
{
    protected $table = 'instance_user';

    public function user(){
         return $this->hasOne('App\User','id','user_id');
    }

    public function amititle(){
        return $this->hasOne('App\Models\Ami','value','ami');
    }

    public function instancetype(){
        return $this->hasOne('App\Models\InstanceType','type','type');
    }
}
