<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::post('login', 'AuthController@login');

Route::middleware(['auth:api'])->group(function () {
    Route::post('logout', 'AuthController@logout');
    Route::post('refresh', 'AuthController@refresh');
    Route::post('me', 'AuthController@me');
    Route::get('/fournisseurs',['as'=>'fournisseurs', 'uses'=>'Fournisseur@getFournisseurs']);
    Route::get('/agences',['as'=>'agences', 'uses'=>'Agence@getAgences']);
    Route::get('/clients/agences','Agence@getAgencesClients');
    Route::get('/achats','AchatsController@getAllAchats');
    Route::get('/achat/{id}','AchatsController@getAchat');
    Route::post('/achat','AchatsController@postAchat');
    Route::delete('/deleteAchat/{id}','AchatsController@deleteAchat');
    Route::post('/updateAchat','AchatsController@updateAchat');
    Route::get('/fournisseurs/{id}',['as'=>'fournisseur', 'uses'=>'Fournisseur@getFournisseur']);
    Route::post('/fournisseur/update/',['as'=>'updateFournisseur', 'uses'=>'Fournisseur@updateFournisseur']);
    Route::post("/fournisseurs","Fournisseur@postFournisseur");
    Route::delete("/fournisseur/{id}","Fournisseur@deleteFournisseur");
    Route::delete("/fournisseur/{fid}/paiments/{pid}","Fournisseur@deletePaiment");
    Route::post("/fournisseur/{fid}/paiments","Fournisseur@addPaiment");
    Route::get('/home', 'HomeController@index')->name('home');


    Route::get('/home', 'HomeController@index')->name('home');
    Route::get('/produits',['as'=>'produits', 'uses'=>'Produit@getProduits']);
    Route::get("/produitsByUser", "Produit@getProductsByUser");
    Route::get('/produits/{id}',['as'=>'produit', 'uses'=>'Produit@getproduit']);
    Route::post('/produit/update/',['as'=>'updateProduit', 'uses'=>'Produit@updateProduit']);
    Route::post("/produits","Produit@postProduit");
    Route::delete("/produit/{id}","Produit@deleteProduit");
    Route::get("/produitHistory/{id}","Produit@productHistory");

    Route::get("/ventes","VenteController@getVentes");
    Route::get("/vente/{id}","VenteController@getVente");
    Route::get("/vente/{id}/pdf","VenteController@ventePDF");
    Route::delete("/vente/{id}","VenteController@deleteVente");
    Route::delete("/vente/{vid}/paiments/{pid}","VenteController@deletePaiment");
    Route::post("/vente","VenteController@postVente");
    Route::put("/vente","VenteController@updateVente");
    Route::get('/home', 'HomeController@index')->name('home');
    Route::get("/clients","Client@getClients");
    Route::post("/client","Client@postClient");
    Route::put("/client","Client@updateClient");
    Route::delete("/client/{id}","Client@deleteClient");
    Route::get("/client/{id}","Client@getClientById");
    Route::get("/commissions","PourcentageController@getPourcentages");
    Route::post("/commission","PourcentageController@postPourcentage");
    Route::put("/commission","PourcentageController@updatePourcentage");
    Route::delete("/commission/{id}","PourcentageController@deletePourcentage");
    Route::get("/commission/{id}","PourcentageController@getPourcentage");

    Route::get("/retours","RetourController@getRetours");
    Route::get("/retour/{id}","RetourController@getRetour");
    Route::delete("/retour/{id}","RetourController@deleteRetour");
    Route::post("/retour","RetourController@postRetour");
    Route::put("/retour","RetourController@updateRetour");

    Route::get("/users","UserController@getUsers");
    Route::get("/user/{id}","UserController@getUserById");
    Route::post("/user","UserController@postUser");
    Route::put("/user","UserController@putUser");

    Route::get("/user/{id}/commissions","CommissionController@getCommissionsByIdUser");
    Route::get("/user/{id}/achats","AchatsController@getAchatsByIdUser");
    Route::get("/user/{id}/ventes","VenteController@getVentesByIdUser");

    Route::get("/historique","HistoryController@getHistorique");
    
    Route::get("/historiqueCredit/{id}","PaimentCreditController@getCreditHistory");
    Route::post("/historiqueCredit/","PaimentCreditController@postCreditPayment");
    Route::delete("/historiqueCredit/{id}","PaimentCreditController@deleteCreditPayment");

    Route::get("/services","ServiceController@getServicesByUserId");
    Route::get("/service/{id}","ServiceController@getService");
    Route::post("/service","ServiceController@postService");
    Route::put("/service","ServiceController@updateService");
    Route::delete("/service/{id}","ServiceController@deleteService"); 

    Route::get("/agences/{agenceId}/charges","ChargeController@getCaisse");
    Route::get("/charge/{id}","ChargeController@getCharge");  
    Route::post("/agences/{agenceId}/charge","ChargeController@postCharge");
    Route::put("/charge","ChargeController@updateCharge");

    Route::get("/statistiques/{agenceId}/{month}/{year}","Agence@getStatisticsByAgenceId");

    Route::get("/paymentVenteCheque","UserController@getPaymentVenteCheque");
    Route::get("/paymentCreditCheque","UserController@getPaymentCreditCheque");
});
