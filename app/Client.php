<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $hidden = array('pivot');
    public function agences()
    {
    	return $this->belongsToMany('App\Agence');
    }

    public function credits()
    {
        return $this->hasMany('App\Credit')->orderBy('created_at', 'desc');
    }

    public function ventes()
    {
        return $this->hasMany('App\Vente');
    }

}
