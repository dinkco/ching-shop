<?php

namespace ChingShop\Modules\Catalogue\Http\Controllers\Staff;

use ChingShop\Http\Controllers\Controller;
use ChingShop\Http\Requests\Staff\Catalogue\ImageOrderRequest;
use ChingShop\Http\Requests\Staff\Catalogue\NewImagesRequest;
use ChingShop\Http\Requests\Staff\Catalogue\Product\PersistProductRequest;
use ChingShop\Http\WebUi;
use ChingShop\Modules\Catalogue\Domain\CatalogueRepository;
use ChingShop\Modules\Catalogue\Domain\Product\Product;
use ChingShop\Modules\Sales\Domain\Offer\Offer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ProductController.
 */
class ProductController extends Controller
{
    /** @var CatalogueRepository */
    private $catalogueRepository;

    /** @var WebUi */
    private $webUi;

    /**
     * ProductController constructor.
     *
     * @param CatalogueRepository $catalogueRepository
     * @param WebUi               $webUi
     */
    public function __construct(
        CatalogueRepository $catalogueRepository,
        WebUi $webUi
    ) {
        $this->catalogueRepository = $catalogueRepository;
        $this->webUi = $webUi;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $products = $this->catalogueRepository->loadLatestProducts();

        return $this->buildView('index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $product = new Product();

        return $this->buildView('create', compact('product'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param PersistProductRequest $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(PersistProductRequest $request)
    {
        $product = $this->catalogueRepository->createProduct($request->all());

        return $this->redirectToShowProduct($product->sku);
    }

    /**
     * Display the specified resource.
     *
     * @param string $sku
     *
     * @throws NotFoundHttpException
     *
     * @return \Illuminate\Http\Response
     */
    public function show(string $sku)
    {
        $product = $this->mustLoadProductBySku($sku);
        $tags = $this->catalogueRepository->loadAllTags();
        $colours = $this->catalogueRepository->loadAllColours();
        $categories = $this->catalogueRepository->loadAllCategories();
        $offers = Offer::all();

        return $this->buildView(
            'show',
            compact(
                'product',
                'tags',
                'colours',
                'categories',
                'offers'
            )
        );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param string $sku
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(string $sku)
    {
        $product = $this->mustLoadProductBySku($sku);

        return $this->buildView('edit', compact('product'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param PersistProductRequest $request
     * @param string                $sku
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Illuminate\Database\Eloquent\MassAssignmentException
     *
     * @return \Illuminate\Http\Response|RedirectResponse
     */
    public function update(PersistProductRequest $request, string $sku)
    {
        $product = $this->catalogueRepository->updateProduct(
            $sku,
            $request->all()
        );

        return $this->redirectToShowProduct($product->sku);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param string $sku
     *
     * @throws \Exception
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $sku)
    {
        $this->catalogueRepository->deleteProductBySku($sku);

        $this->webUi->successMessage("Deleted product `{$sku}`");

        return $this->webUi->redirect('products.index');
    }

    /**
     * @param int $productId
     * @param int $imageId
     *
     * @return RedirectResponse
     */
    public function detachImage(int $productId, int $imageId)
    {
        $product = $this->catalogueRepository->loadProductById($productId);
        $image = $this->catalogueRepository->loadImageById($imageId);

        $this->catalogueRepository->detachImageFromOwner($image, $product);

        $this->webUi->successMessage(
            "Removed one image from product `{$product->sku}`."
        );

        return $this->redirectToShowProduct($product->sku);
    }

    /**
     * @param string            $sku
     * @param ImageOrderRequest $request
     *
     * @return JsonResponse
     */
    public function putImageOrder(string $sku, ImageOrderRequest $request)
    {
        $this->catalogueRepository->updateImageOrder(
            $this->catalogueRepository->loadProductBySku($sku),
            $request->imageOrder()
        );

        return $this->webUi->json($request->imageOrder(), 200);
    }

    /**
     * @param NewImagesRequest $request
     * @param string           $sku
     *
     * @throws \Symfony\Component\HttpFoundation\File\Exception\FileException
     *
     * @return RedirectResponse
     */
    public function postProductImages(NewImagesRequest $request, string $sku)
    {
        $product = $this->catalogueRepository->loadProductBySku($sku);
        $this->persistUploadedImages($request, $product);

        return $this->redirectToShowProduct($product->sku);
    }

    /**
     * @param       $name
     * @param array $bindData
     *
     * @return View
     */
    private function buildView($name, array $bindData = []): View
    {
        return $this->webUi->view(
            "catalogue::staff.products.{$name}",
            $bindData
        );
    }

    /**
     * @param string $sku
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *
     * @return Product
     */
    private function mustLoadProductBySku(string $sku): Product
    {
        $product = $this->catalogueRepository->loadProductBySku($sku);
        if (!$product->isStored()) {
            throw new NotFoundHttpException();
        }

        return $product;
    }

    /**
     * @param string $sku
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    private function redirectToShowProduct(string $sku): RedirectResponse
    {
        return $this->webUi->redirect('products.show', ['sku' => $sku]);
    }

    /**
     * @param NewImagesRequest $request
     * @param Product          $product
     *
     * @throws \Symfony\Component\HttpFoundation\File\Exception\FileException
     */
    private function persistUploadedImages(
        NewImagesRequest $request,
        Product $product
    ) {
        if (!$request->hasNewImages()) {
            return;
        }

        $this->catalogueRepository->attachUploadedImagesToProduct(
            $request->newImages(),
            $product
        );
    }
}
