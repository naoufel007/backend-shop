<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    public function vente()
    {
    	return $this->belongsTo("App\Vente");
    }

    public function produit()
    {
    	return $this->belongsTo("App\Produit");
    }
}
