<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Fournisseur extends Model
{
    protected $table='fournisseurs';
    protected $hidden = array('pivot');
    public function agences()
    {
    	return $this->belongsToMany('App\Agence');
    }

    public function paiments()
    {
        return $this->hasMany('App\Paiment')->orderBy('created_at','DESC');
    }

    public function achats()
    {
        return $this->hasMany('App\Achat');
    }
}
