<?php

use Payfast\PayfastCommon\Aggregator\Request\PaymentRequest;

class PayfastLogger
{
    private PaymentRequest $paymentRequest;

    public function __construct(PaymentRequest $paymentRequest)
    {
        $this->paymentRequest = $paymentRequest;
    }

    public function log(string $message): void
    {
        $this->paymentRequest->pflog($message);
    }

    public function close(): void
    {
        $this->paymentRequest->pflog('');
    }
}
