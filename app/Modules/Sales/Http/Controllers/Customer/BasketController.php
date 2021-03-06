<?php

namespace ChingShop\Modules\Sales\Http\Controllers\Customer;

use Analytics;
use ChingShop\Http\Controllers\Controller;
use ChingShop\Http\WebUi;
use ChingShop\Modules\Catalogue\Domain\Product\ProductOptionRepository;
use ChingShop\Modules\Sales\Domain\Clerk;
use ChingShop\Modules\Sales\Domain\Payment\StockAllocationException;
use ChingShop\Modules\Sales\Http\Requests\Customer\AddToBasketRequest;
use ChingShop\Modules\Sales\Http\Requests\Customer\RemoveFromBasketRequest;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class BasketController.
 */
class BasketController extends Controller
{
    /** @var Clerk */
    private $clerk;

    /** @var ProductOptionRepository */
    private $optionRepository;

    /** @var WebUi */
    private $webUi;

    /**
     * BasketController constructor.
     *
     * @param Clerk                   $clerk
     * @param ProductOptionRepository $optionRepository
     * @param WebUi                   $webUi
     */
    public function __construct(
        Clerk $clerk,
        ProductOptionRepository $optionRepository,
        WebUi $webUi
    ) {
        $this->clerk = $clerk;
        $this->optionRepository = $optionRepository;
        $this->webUi = $webUi;
    }

    /**
     * @param AddToBasketRequest $request
     *
     * @throws \ChingShop\Modules\Sales\Domain\Payment\StockAllocationException
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function addProductOptionAction(AddToBasketRequest $request)
    {
        $productOption = $this->optionRepository->loadById(
            $request->optionId()
        );

        try {
            $this->clerk->addProductOptionToBasket($productOption);
        } catch (StockAllocationException $e) {
            $this->webUi->warningMessage($e->getMessage());

            return $this->webUi->redirectAway($productOption->product->url());
        }

        $this->webUi->successMessage(
            sprintf(
                '1 &#215; <strong>%s (%s)</strong> was added to your basket.',
                $productOption->product->name,
                $productOption->label
            )
        );

        Analytics::trackEvent(
            'basket',
            'add',
            $productOption->product->sku,
            $productOption->label
        );

        return $this->webUi->redirect('sales.customer.basket');
    }

    /**
     * View the shopping basket.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function viewBasketAction()
    {
        return $this->webUi->view('customer.basket.view');
    }

    /**
     * @param RemoveFromBasketRequest $request
     *
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @throws \Exception
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function removeBasketItemAction(RemoveFromBasketRequest $request)
    {
        if (!$this->clerk->basket()->getItem($request->basketItemId())->id) {
            throw new BadRequestHttpException(
                sprintf(
                    'Basket does not contain any item with id `%s`.',
                    $request->basketItemId()
                )
            );
        }

        /** @var $item */
        $item = $this->clerk->removeBasketItem($request->basketItemId());

        $this->webUi->successMessage(
            sprintf(
                '1 &#215; <strong>%s (%s)</strong> %s',
                $item->productOption->product->name,
                $item->productOption->label,
                ' was removed from your basket.'
            )
        );

        Analytics::trackEvent(
            'basket',
            'remove',
            $request->basketItemId()
        );

        return $this->webUi->redirect('sales.customer.basket');
    }
}
