<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Vente extends Model
{
    
    public function agence()
    {
        return $this->belongsTo("App\Agence");
    }
    public function produits()
    {
    	return $this
    			-> belongsToMany("App\Produit","vente_produit")
    			-> withPivot("quantite_vente_produit","prix_vente","remise","type");
    }
    public function client()
    {
        return $this->belongsTo("App\Client");
    }

    public function user()
    {
        return $this->belongsTo("App\User");
    }

    public function paiments()
    {
        return $this->hasMany("App\PaimentVente");
    }

}
