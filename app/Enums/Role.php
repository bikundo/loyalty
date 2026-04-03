<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case MerchantOwner = 'merchant_owner';
    case MerchantManager = 'merchant_manager';
    case Cashier = 'cashier';
    case Customer = 'customer';
    case ApiClient = 'api_client';
}
