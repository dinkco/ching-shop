<?php

namespace ChingShop\Http\Controllers\Customer;

use ChingShop\Support\Arr;
use ChingShop\Http\Controllers\Controller;
use Illuminate\Contracts\Routing\ResponseFactory;
use ChingShop\Catalogue\Product\ProductRepository;
use Illuminate\Contracts\View\Factory as ViewFactory;

class RootController extends Controller
{
    /** @var ProductRepository */
    private $productRepository;

    /** @var ViewFactory */
    private $viewFactory;

    /** @var ResponseFactory */
    private $responseFactory;

    /**
     * ProductController constructor.
     *
     * @param ProductRepository $productRepository
     * @param ViewFactory       $viewFactory
     * @param ResponseFactory   $responseFactory
     */
    public function __construct(
        ProductRepository $productRepository,
        ViewFactory $viewFactory,
        ResponseFactory $responseFactory
    ) {
        $this->productRepository = $productRepository;
        $this->viewFactory = $viewFactory;
        $this->responseFactory = $responseFactory;
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getIndex()
    {
        $productColumns = Arr::partition(
            $this->productRepository->presentLatest(8),
            4
        );

        return $this->viewFactory->make('welcome', compact('productColumns'));
    }
}
