<?php

namespace App\Models;

use Database\Factories\CashierFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cashier extends Model
{
    /** @use HasFactory<CashierFactory> */
    use HasFactory;
}
