<?php

namespace IranKish\Enums;

/**
 * @see https://www.irankish.com/App_Data_Public/IPG/IPG_TechnicalGuide.V9.pdf#page=19
 * Available transaction types for IranKish gateway.
 */
enum TransactionType: string
{
    case PURCHASE = 'Purchase'; // Standard purchase
    case BILL = 'Bill'; // Bill payment
    case ASAN_SHP_WPP = 'AsanShpWPP'; // EasyBuy with prepayment
    case SPECIAL_BILL = 'SpecialBill'; // Bill with confirmation
    case ASAN_SHP_WPP_DRUG = 'AsanShpWPPDrug'; // EasyBuy (Drug)
    case ISACO_WPP = 'IsacoWPP'; // ISACO credit purchase
}
