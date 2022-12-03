<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Login extends Model
{
    public function user()
    {
        return $this->belongsTo("App\User");
    }
}
