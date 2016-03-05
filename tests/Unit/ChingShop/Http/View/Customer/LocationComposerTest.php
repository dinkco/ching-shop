<?php

namespace Testing\Unit\ChingShop\Http\Customer\View;

use ChingShop\Catalogue\Product\ProductPresenter;
use ChingShop\Http\View\Customer\LocationComposer;
use ChingShop\Http\View\Customer\Viewable;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Router;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Testing\Unit\UnitTest;

class LocationComposerTest extends UnitTest
{
    /** @var LocationComposer */
    private $locationComposer;

    /** @var Router|MockObject */
    private $router;

    /** @var UrlGenerator|MockObject */
    private $urlGenerator;

    /** @var View|MockObject */
    private $view;

    /**
     * Set up customer location composer with mock dependencies.
     */
    public function setUp()
    {
        parent::setUp();

        $this->router = $this->makeMock(Router::class);
        $this->urlGenerator = $this->makeMock(UrlGenerator::class);

        $this->locationComposer = new LocationComposer(
            $this->router,
            $this->urlGenerator
        );

        $this->view = $this->makeMock(View::class);
    }

    /**
     * Sanity check for instantiation.
     */
    public function testConstruct()
    {
        $this->assertInstanceOf(
            LocationComposer::class,
            $this->locationComposer
        );
    }

    /**
     * Should compose view with self.
     */
    public function testCompose()
    {
        $this->view->expects($this->once())
            ->method('with')
            ->with(['location' => $this->locationComposer]);

        $this->locationComposer->compose($this->view);
    }

    /**
     * Should be able to get the view href for a viewable object.
     */
    public function testViewHrefFor()
    {
        /** @var Viewable|MockObject $viewable */
        $viewable = $this->makeMock(Viewable::class);
        $viewable->expects($this->once())->method('routePrefix');
        $viewable->expects($this->once())->method('locationParts');

        $this->urlGenerator->expects($this->once())
            ->method('route')
            ->willReturn($this->generator()->anySlug());

        $viewHref = $this->locationComposer->viewHrefFor($viewable);

        $this->assertInternalType('string', $viewHref);
    }

    /**
     * Should be able to get the mailto href for a product presenter.
     */
    public function testProductEnquiryMail()
    {
        /** @var ProductPresenter|MockObject $productPresenter */
        $productPresenter = $this->makeMock(ProductPresenter::class);
        $productPresenter->expects($this->once())->method('name');
        $productPresenter->expects($this->once())->method('SKU');

        $mailto = $this->locationComposer->productEnquiryMail(
            $productPresenter
        );

        $this->assertInternalType('string', $mailto);
    }
}
