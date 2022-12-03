<?php

namespace App;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];


    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    
    public function ventes(){
        return $this->hasMany("App\Vente");
    }

    public function achats(){
        return $this->hasMany("App\Achat");
    }

    public function agence()
    {
        return $this->belongsTo("App\Agence");
    }
    public function retours(){
        return $this->hasMany("App\Retour");
    }

    public function commissions()
    {
        $now = Carbon::now();
        $day1 = Carbon::createFromDate($now->year,$now->month,1);
        return $this->hasMany("App\Commission")
                    ->where('created_at', '<=', $now->toDateString())
                    ->where('created_at', '>=', $day1->toDateString());
    }

    public function commissionSum()
    {
        $now = Carbon::now();
        $day1 = Carbon::createFromDate($now->year,$now->month,1)->toDateString();

        return $this->hasMany("App\Commission")
                ->selectRaw("user_id,sum(commission) as commission")
                ->where('created_at', '>=', $day1)
                ->groupBy("user_id");
    }

    public function services(){
        return $this->hasMany("App\Service");
    }
}
