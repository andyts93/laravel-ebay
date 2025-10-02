<?php

namespace Andyts93\LaravelEbay\Enums;

enum StoreTypeEnum: string
{
    case STORE = 'STORE';
    case WAREHOUSE = 'WAREHOUSE';
    case FULFILLMENT_CENTER = 'FULFILLMENT_CENTER';
}
