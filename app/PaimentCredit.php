<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaimentCredit extends Model
{
    public function Credit()
    {
        return $this->belongsTo('App\Credit');
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
