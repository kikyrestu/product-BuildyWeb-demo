<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaundryProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'laundry_name',
        'logo_path',
        'owner_name',
        'phone',
        'whatsapp',
        'email',
        'address',
        'city',
        'postal_code',
        'invoice_footer_note',
    ];
}
