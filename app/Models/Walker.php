<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Walker extends Model
{

    protected $fillable = [
        'name', 'email', 'experience', 'photo_url', 'rating', 'total_reviews'
    ];

    public function reservations()
    {
        return $this->hasMany(WalkReservation::class, 'walker_id');
    }
}
