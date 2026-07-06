<?php

namespace App\Support;

final class SubconConstants
{
    // subcon_trips.kind
    public const TRIP_HATID        = 'hatid';         // deliver cut pieces to subcon
    public const TRIP_KUHA         = 'kuha';          // pick up finished sewing
    public const TRIP_EXTRA_KULANG = 'extra_kulang';  // extra trip because of shortage
    public const TRIP_EXTRA_NAIWAN = 'extra_naiwan';  // extra trip because something was left behind

    public const TRIP_KINDS = [
        self::TRIP_HATID, self::TRIP_KUHA, self::TRIP_EXTRA_KULANG, self::TRIP_EXTRA_NAIWAN,
    ];

    public static function isExtraTrip(string $kind): bool
    {
        return $kind === self::TRIP_EXTRA_KULANG || $kind === self::TRIP_EXTRA_NAIWAN;
    }

    // subcon_pos.status
    public const PO_OPEN     = 'open';
    public const PO_COMPLETE = 'complete';

    // subcon_attachments.owner_type
    public const OWNER_DELIVERY = 'delivery';
    public const OWNER_TRIP     = 'trip';
    public const OWNER_PO       = 'po';

    // finance posting sources (match the finance module convention)
    public const FIN_SRC_SUBCON  = 'subcon';   // weekly sewing payout
    public const FIN_SRC_SUBLOG  = 'sublog';   // logistics trip
}
