<?php

namespace Saleh7\Zatca\Enums;

/**
 * ZATCA Unit Codes
 *
 * Standard units of measurement for invoice line items
 * Based on UN/CEFACT Common Code list
 */
enum UnitCodeEnum: string
{
    case Piece = 'PCE';

    case Kilogram = 'KGM';

    case Meter = 'MTR';

    case Liter = 'LTR';

    case Unit = 'C62';

    case Hour = 'HUR';

    case Day = 'DAY';

    case SquareMeter = 'MTK';

    case CubicMeter = 'MTQ';

    case Gram = 'GRM';

    case Tonne = 'TNE';

    case Centimeter = 'CMT';

    case Millimeter = 'MMT';

    case Kilometer = 'KMT';

    case SquareKilometer = 'KMK';

    case Milliliter = 'MLT';

    case Barrel = 'BLL';

    case Box = 'BX';

    case Carton = 'CT';

    case Pack = 'PA';

    case Can = 'CA';

    case Dozen = 'DZN';

    case Each = 'EA';

    case Gallon = 'GLL';

    case Set = 'SET';

    case Pair = 'PR';

    case Roll = 'RO';

    case Minute = 'MIN';

    case Second = 'SEC';

    case Week = 'WEE';

    case Month = 'MON';

    case Year = 'ANN';
}
