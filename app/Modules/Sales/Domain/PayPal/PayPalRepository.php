<?php

namespace ChingShop\Modules\Sales\Domain\PayPal;

use ChingShop\Modules\Sales\Domain\Basket\Basket;
use ChingShop\Modules\Sales\Domain\Payment\Cashier;
use ChingShop\Modules\Sales\Events\NewPayPalSettlementEvent;
use Log;
use PayPal\Api\Payment;
use PayPal\Rest\ApiContext;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Manages persistence of PayPal-related models.
 *
 * Class PayPalCheckoutFactory.
 */
class PayPalRepository
{
    /** @var ApiContext */
    private $apiContext;

    /** @var Cashier */
    private $cashier;

    /** @var LoggerInterface */
    private $log;

    /**
     * PayPalCheckoutFactory constructor.
     *
     * @param ApiContext      $apiContext
     * @param Cashier         $cashier
     * @param LoggerInterface $logger
     */
    public function __construct(
        ApiContext $apiContext,
        Cashier $cashier,
        LoggerInterface $logger
    ) {
        $this->apiContext = $apiContext;
        $this->cashier = $cashier;
        $this->log = $logger;
    }

    /**
     * @param Basket $basket
     *
     * @return PayPalCheckout
     */
    public function makeCheckout(Basket $basket): PayPalCheckout
    {
        $this->log->info("Starting PayPal checkout for basket {$basket->id}");
        $basket->load(
            [
                'basketItems.productOption.product.prices',
                'address',
            ]
        );

        return new PayPalCheckout($basket, $this->apiContext);
    }

    /**
     * @param PayPalCheckout $payPalCheckout
     *
     * @throws \InvalidArgumentException
     *
     * @return PayPalInitiation
     */
    public function createInitiation(
        PayPalCheckout $payPalCheckout
    ): PayPalInitiation {
        /** @var PayPalInitiation $payPalInitiation */
        $payPalInitiation = PayPalInitiation::firstOrNew(
            [
                'payment_id' => $payPalCheckout->paymentId(),
                'amount'     => $payPalCheckout->amountTotal(),
            ]
        );

        $payPalInitiation->basket()->associate($payPalCheckout->basketId());
        $payPalInitiation->save();

        return $payPalInitiation;
    }

    /**
     * @param string $paymentId
     *
     * @return PayPalInitiation
     */
    public function loadInitiation(string $paymentId): PayPalInitiation
    {
        return PayPalInitiation::where(
            'payment_id',
            '=',
            $paymentId
        )->first() ?? new PayPalInitiation();
    }

    /**
     * @param string $paymentId
     * @param string $payerId
     *
     * @throws \Exception
     * @throws \InvalidArgumentException
     * @throws RuntimeException
     *
     * @return \ChingShop\Modules\Sales\Domain\Order\Order
     */
    public function executePayment(string $paymentId, string $payerId)
    {
        $settlement = new PayPalSettlement(
            [
                'payment_id' => $paymentId,
                'payer_id'   => $payerId,
            ]
        );
        $this->log->info("Executing PayPal payment {$paymentId} / {$payerId}");
        $execution = $this->createExecution($paymentId, $payerId);
        $settlement->payer_email = $execution->payerEmail();
        $settlement->save();
        $order = $this->cashier->settle($execution->basket(), $settlement);

        if ($execution->approve()) {
            $this->dispatchSettlement($settlement);

            return $order;
        }

        throw new RuntimeException('PayPal payment was not approved.');
    }

    /**
     * @param string $paymentId
     * @param string $payerId
     *
     * @throws RuntimeException
     *
     * @return PayPalExecution
     */
    private function createExecution(string $paymentId, string $payerId)
    {
        /** @var Payment $payment */
        $payment = app(Payment::class);

        $initiation = $this->loadInitiation($paymentId);
        if (!$initiation instanceof PayPalInitiation || !$initiation->id) {
            throw new RuntimeException(
                "Failed to load PayPal initiation for payment `{$paymentId}`."
            );
        }

        return new PayPalExecution(
            $initiation,
            new PayPalReturn(
                $payment->get($paymentId, $this->apiContext),
                $payerId
            ),
            $this->apiContext
        );
    }

    /**
     * @param $settlement
     */
    private function dispatchSettlement($settlement)
    {
        try {
            event(new NewPayPalSettlementEvent($settlement));
        } catch (Throwable $e) {
            Log::warning($e->getMessage());
        }
    }
}
