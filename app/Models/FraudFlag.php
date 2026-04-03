<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Database\Factories\FraudFlagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FraudFlag extends Model
{
    /** @use HasFactory<FraudFlagFactory> */
    use HasFactory;
}
