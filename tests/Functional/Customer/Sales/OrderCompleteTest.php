<?php

namespace Testing\Functional\Customer\Sales;

use Testing\Functional\FunctionalTest;
use Testing\Functional\Util\MockPayPal;
use Testing\Functional\Util\SalesInteractions;

/**
 * Class OrderCompleteTest
 * @package Testing\Functional\Customer\Sales
 *
 * Confirm that the order completion page works correctly.
 *
 * @group sales
 * @group checkout
 */
class OrderCompleteTest extends FunctionalTest
{
    use SalesInteractions, MockPayPal;

    /**
     * Should be able to see the contents of the order on the order completion
     * page.
     */
    public function testCanSeeOrderItems()
    {
        $this->markTestIncomplete();
    }

    /**
     * Should be able to see the delivery address on the order completion page.
     */
    public function testCanSeeOrderAddress()
    {
        // When I complete an order;
        $address = $this->completeCheckoutAddress($this);
        $this->customerWillReturnFromPayPal();
        $this->press('Pay with PayPal');

        // Then I should be able to see my delivery address on the order
        // completion page.
        $this->see($address->name);
        $this->see($address->line_one);
        $this->see($address->city);
        $this->see($address->post_code);
        $this->see($address->country_code);

        $this->markTestIncomplete();
    }
}
