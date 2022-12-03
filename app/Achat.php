<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Achat extends Model
{
	protected $hidden = ["pivot"];
    public function fournisseur()
    {
    	return $this->belongsTo("App\Fournisseur");
    }

    public function agence()
    {
        return $this->belongsTo("App\Agence");
    }

    public function client()
    {
        return $this->belongsTo("\App\Client");
    }

    public function user()
    {
        return $this->belongsTo("\App\User");
    }

    public function produits()
    {
    	return $this
    			-> belongsToMany("App\Produit","achat_produit")
    			-> withPivot("quantite_achat_produit","prix_achat","remise","type","quantite_restante");
    }


}
