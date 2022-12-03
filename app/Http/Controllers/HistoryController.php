<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
class HistoryController extends Controller
{
    public function getHistorique()
    {
        $rawLogins = \App\Login::with("user:id,name,agence_id","user.agence")
                        ->orderBy('created_at', 'DESC')
                        ->get();
        
        $logins = [];

    	foreach ($rawLogins as $login) {
    		
    		$logins[] = [
    			"id" => $login["id"],
                "user_id" => $login["user_id"],
                "username" => $login["user"]["name"],
                "agence" => $login["user"]["agence"]["nom"],
    			"date" => $login["created_at"],
                "type" => $login["type"] == "c" ? "connexion" : "deconnexion"
    		];
    		
    	}
    	return $logins;
    }
}
