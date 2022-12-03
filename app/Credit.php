<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function paiments()
    {
        return $this->hasMany('App\PaimentCredit');
    }

    public function agence()
    {
        return $this->belongsTo('App\Agence');
    }
}
