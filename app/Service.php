<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    public function agence()
    {
        return $this->belongsTo("App\Agence");
    }
	
    public function user()
    {
        return $this->belongsTo("App\User");
    }
}
