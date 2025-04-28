<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalkReservation extends Model
{


    protected $fillable = [
        'client_id', 'dog_id', 'walker_id',
        'reservation_date', 'reservation_time', 'status'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function dog()
    {
        return $this->belongsTo(Dog::class, 'dog_id');
    }

    public function walker()
    {
        return $this->belongsTo(Walker::class, 'walker_id');
    }
}
