<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
class Agence extends Model
{
    protected $fillable = ['id', 'nom', 'addresse'];
    protected $hidden = array('pivot');
    
    public function employees(){
    	return $this->hasMany("App\User");
    }

    public function achats()
    {
    	return $this->hasMany("App\Achat");
    }


    public function users()
    {
    	return $this->hasMany("App\User");
    }

    public function ventes()
    {
    	return $this->hasMany("App\Vente");
    }

    public function fournisseurs()
    {
        return $this->belongToMany('App\Fournisseur');
    }

    public function clients()
    {
        return $this->belongsToMany('App\Client');
    }

    public function retours()
    {
        return $this->hasMany("App\Retour");
    }

    public function services()
    {
        return $this->hasMany("App\Service");
    }

    public function charges()
    {
        return $this->hasMany("App\Charge");
    }
}
