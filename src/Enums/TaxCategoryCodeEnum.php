<?php

namespace Saleh7\Zatca\Enums;

/**
 * ZATCA VAT Category Codes
 *
 * Source:
 * - UN/ECE 5305
 * - ZATCA E-Invoicing (KSA) VAT implementation
 *
 * These codes define how VAT is applied to a supply.
 */
enum TaxCategoryCodeEnum: string
{
    /**
     * Standard rated VAT.
     *
     * Applied to taxable supplies with a positive VAT rate.
     * (e.g. 15% in Saudi Arabia)
     */
    case Standard = 'S';

    /**
     * Zero rated VAT.
     *
     * VAT rate is 0%, but the supply is still taxable and must be reported to ZATCA.
     */
    case Zero = 'Z';

    /**
     * Exempt from VAT.
     *
     * Supply is exempt under VAT law.
     */
    case Exempt = 'E';

    /**
     * Outside scope of VAT.
     *
     * Supply is not subject to VAT at all.
     * (e.g. non-KSA transactions)
     */
    case Outside = 'O';
}
