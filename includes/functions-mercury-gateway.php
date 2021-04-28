<?php

use MercuryCash\SDK\Auth\APIKey;
use MercuryCash\SDK\Adapter;
use MercuryCash\SDK\Endpoints\Transaction;

function Mercury_Gateway()
{
    if(is_callable('Mercury_Gateway::instance'))
    {
        return Mercury_Gateway::instance();
    }
    return false;
}


