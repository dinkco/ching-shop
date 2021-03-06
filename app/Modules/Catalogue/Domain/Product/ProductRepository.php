<?php

namespace ChingShop\Modules\Catalogue\Domain\Product;

use Generator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Log;
use Throwable;

/**
 * Class ProductRepository.
 */
class ProductRepository
{
    /** @var Product|Builder */
    private $productResource;

    /**
     * @param Product $productResource
     */
    public function __construct(Product $productResource)
    {
        $this->productResource = $productResource;
    }

    /**
     * @return Product
     */
    public function product(): Product
    {
        return $this->productResource;
    }

    /**
     * @return Generator|Product[]
     */
    public function iterateAll(): Generator
    {
        /** @var Product $product */
        foreach ($this->productResource->with('images')->cursor() as $product) {
            if (!$product->relationLoaded('images')) {
                $product->load('images');
            }
            yield $product;
        }
    }

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function loadLatest()
    {
        return $this->productResource
            ->orderBy('updated_at', 'desc')
            ->with(Product::standardRelations())
            ->paginate();
    }

    /**
     * @param array $productData
     *
     * @return Product
     */
    public function create(array $productData): Product
    {
        return $this->productResource->create($productData);
    }

    /**
     * @param string $sku
     * @param array  $newData
     *
     * @throws \Illuminate\Database\Eloquent\MassAssignmentException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     *
     * @return Product
     */
    public function update(string $sku, array $newData): Product
    {
        $product = $this->productResource->where('sku', $sku)->firstOrFail();
        $product->fill($newData);
        $product->save();

        return $product;
    }

    /**
     * @param string $sku
     *
     * @return Product
     */
    public function loadBySku(string $sku): Product
    {
        $product = $this->productResource
            ->where('sku', $sku)
            ->with(Product::standardRelations())
            ->first();

        return $product ?: new Product();
    }

    /**
     * @param int $productId
     *
     * @return Product
     */
    public function loadById(int $productId): Product
    {
        return $this->productResource
            ->where('id', $productId)
            ->with(Product::standardRelations())
            ->first();
    }

    /**
     * @param int $productId
     *
     * @return Product
     */
    public function loadAlone(int $productId): Product
    {
        return $this->productResource->where('id', $productId)->first();
    }

    /**
     * @param Product $product
     *
     * @return Collection
     */
    public function loadSimilar(Product $product): Collection
    {
        try {
            return $this->productResource
                ->search($product->name)
                ->take(4)
                ->get()
                ->filter(
                    function (Product $similarProduct) use ($product) {
                        return $similarProduct->id !== $product->id;
                    }
                );
        } catch (Throwable $e) {
            Log::error($e->getMessage());

            return new Collection();
        }
    }

    /**
     * @return Paginator
     */
    public function loadInStock(): Paginator
    {
        return $this->productResource
            ->inStock()
            ->with(Product::standardRelations())
            ->orderBy('updated_at', 'desc')
            ->take(120)
            ->paginate();
    }

    /**
     * @param string $sku
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function deleteBySku(string $sku)
    {
        return (bool) $this->productResource
            ->where('sku', $sku)
            ->limit(1)
            ->first()
            ->delete();
    }

    /**
     * @param string $sku
     * @param int    $units
     * @param int    $subunits
     *
     * @return bool
     */
    public function setPriceBySku(string $sku, int $units, int $subunits)
    {
        /** @var Product $product */
        $product = $this->productResource
            ->where('sku', $sku)
            ->with('prices')
            ->limit(1)
            ->first();

        $price = $product->prices()->firstOrNew([]);
        $price->setAttribute('units', $units);
        $price->setAttribute('subunits', $subunits);
        $price->setAttribute('currency', 'GBP');

        return $price->save();
    }

    /**
     * @return Product
     */
    public function resource(): Product
    {
        return $this->productResource;
    }
}
