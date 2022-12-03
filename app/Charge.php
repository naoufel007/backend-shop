<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Charge extends Model
{
    public function agence()
    {
        return $this->belongsTo("App\Agence");
    }
}
