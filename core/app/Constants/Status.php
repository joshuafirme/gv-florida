<?php

namespace App\Constants;

class Status{

    const ENABLE = 1;
    const DISABLE = 0;

    const YES = 1;
    const NO = 0;

    const VERIFIED = 1;
    const UNVERIFIED = 0;

    const PAYMENT_INITIATE = 0;
    const PAYMENT_SUCCESS = 1;
    const PAYMENT_PENDING = 2;
    const PAYMENT_REJECT = 3;

    CONST TICKET_OPEN = 0;
    CONST TICKET_ANSWER = 1;
    CONST TICKET_REPLY = 2;
    CONST TICKET_CLOSE = 3;

    CONST PRIORITY_LOW = 1;
    CONST PRIORITY_MEDIUM = 2;
    CONST PRIORITY_HIGH = 3;

    const USER_ACTIVE = 1;
    const USER_BAN = 0;

    const GOOGLE_PAY = 5001;

    const CUR_BOTH = 1;
    const CUR_TEXT = 2;
    const CUR_SYM = 3;

    const BOOKED_REJECTED = 0;
    const BOOKED_APPROVED = 1;
    const BOOKED_PENDING = 2;

    const TRIP_ON_TIME = 'on_time';
    const TRIP_BOARDING = 'boarding';
    const TRIP_DELAYED = 'delayed';
    const TRIP_CANCELLED = 'cancelled';

}
