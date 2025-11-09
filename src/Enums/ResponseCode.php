<?php

namespace IranKish\Enums;

/**
 * Common response codes returned by IranKish endpoints.
 * Extend this enum based on the official table:
 * @see https://www.irankish.com/App_Data_Public/IPG/IPG_TechnicalGuide.V9.pdf#page=44
 *
 * NOTE: Use ResponseCode::tryFrom($code) to map safely;
 *       for unknown codes, fallback gracefully in your app.
 */
enum ResponseCode: string
{
    case SUCCESS = '00';            // Transaction successful
    case REFER_TO_CARD_ISSUER = '01';  // Refer to card issuer
    case INVALID_MERCHANT = '03';      // Invalid merchant/acceptor/terminal
    case PICK_UP_CARD = '04';          // Pick up card
    case DO_NOT_HONOR = '05';          // Do not honor
    case INVALID_TRANSACTION = '12';   // Invalid transaction
    case INVALID_AMOUNT = '13';        // Invalid amount
    case INVALID_CARD_NUMBER = '14';   // Invalid card number
    case NO_SUFFICIENT_FUNDS = '51';   // Insufficient funds
    case EXPIRED_CARD = '54';          // Expired card
    case INCORRECT_PIN = '55';         // Incorrect PIN
    case TRANSACTION_NOT_PERMITTED = '57'; // Transaction not permitted
    case SUSPECTED_FRAUD = '59';       // Suspected fraud
    case SYSTEM_MALFUNCTION = '96';    // System malfunction
    case UNKNOWN_FAILURE = '99';       // Unknown/general failure

    public function description(): string
    {
        return match ($this) {
            self::SUCCESS => 'Transaction successful.',
            self::REFER_TO_CARD_ISSUER => 'Refer to card issuer.',
            self::INVALID_MERCHANT => 'Invalid acceptor/terminal or not allowed.',
            self::PICK_UP_CARD => 'Pick up card.',
            self::DO_NOT_HONOR => 'Do not honor.',
            self::INVALID_TRANSACTION => 'Invalid transaction.',
            self::INVALID_AMOUNT => 'Invalid amount.',
            self::INVALID_CARD_NUMBER => 'Invalid card number.',
            self::NO_SUFFICIENT_FUNDS => 'Insufficient funds.',
            self::EXPIRED_CARD => 'Expired card.',
            self::INCORRECT_PIN => 'Incorrect PIN.',
            self::TRANSACTION_NOT_PERMITTED => 'Transaction not permitted.',
            self::SUSPECTED_FRAUD => 'Suspected fraud.',
            self::SYSTEM_MALFUNCTION => 'System malfunction.',
            self::UNKNOWN_FAILURE => 'General/unknown failure.',
        };
    }
}
