<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Produit extends Model
{
    public function fournisseur()
    {
    	return $this->belongsTo("App\Fournisseur");
    }

    public function achats()
    {
    	return $this->belongsToMany('App\Achat');
    }

    public function ventes()
    {
    	return $this->belongsToMany('App\Vente');
    }

    public function retours()
    {
    	return $this->belongsToMany('App\Retour');
    }
}
