<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dog extends Model
{

    protected $fillable = [
        'name', 'breed', 'age', 'size', 'energy_level', 'photo_url'
    ];

    public function clients()
    {
        return $this->belongsToMany(Client::class, 'client_dog', 'dog_id', 'client_id');
    }

    public function reservations()
    {
        return $this->hasMany(WalkReservation::class, 'dog_id');
    }
}
