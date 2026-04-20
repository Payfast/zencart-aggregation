<?php

use Payfast\PayfastCommon\Aggregator\Request\PaymentRequest;

class PayfastITN
{
    private PaymentRequest $paymentRequest;
    private array|false $data;
    private string $pfParamString;

    public function __construct()
    {
        $this->paymentRequest = new PaymentRequest(true);
        $this->data           = $this->paymentRequest->pfGetData();
        $this->pfParamString  = '';
    }

    public function getData(): array|false
    {
        return $this->data;
    }

    public function isSignatureValid(string $passphrase): bool
    {
        return $this->paymentRequest->pfValidSignature($this->data, $this->pfParamString, $passphrase);
    }

    public function isDataValid(array $moduleInfo, string $host): bool
    {
        return $this->paymentRequest->pfValidData($moduleInfo, $host, $this->pfParamString);
    }

    public function amountsEqual(float|int $amount1, float|int $amount2): bool
    {
        return $this->paymentRequest->pfAmountsEqual($amount1, $amount2);
    }

    public function getPaymentRequest(): PaymentRequest
    {
        return $this->paymentRequest;
    }

    // Expose constants from PaymentRequest for error handling
    public const PF_ERR_BAD_ACCESS        = 'An invalid request was sent to the server';
    public const PF_ERR_INVALID_SIGNATURE = 'Invalid signature';
    public const PF_ERR_NO_SESSION        = 'No saved session found';
    public const PF_ERR_AMOUNT_MISMATCH   = 'Amount mismatch';
}
