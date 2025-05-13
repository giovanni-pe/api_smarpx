<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{


    protected $fillable = [
        'name',
        'email',
        'phone',
        'address'
    ];

    public function dogs()
    {
        return $this->belongsToMany(Dog::class, 'client_dog', 'client_id', 'dog_id');
    }

    public function reservations()
    {
        return $this->hasMany(WalkReservation::class, 'client_id');
    }
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_client', 'client_id', 'user_id');
    }
}
