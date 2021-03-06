<?php

Route::group(
    [
        'prefix' => 'shopping',
    ],
    function () {
        Route::post(
            'add-to-basket',
            [
                'as'   => 'sales.customer.add-to-basket',
                'uses' => 'Customer\BasketController@addProductOptionAction',
            ]
        );
        Route::get(
            'basket',
            [
                'as'   => 'sales.customer.basket',
                'uses' => 'Customer\BasketController@viewBasketAction',
            ]
        )->middleware(['customer', 'suggestions']);
        Route::post(
            'remove-from-basket',
            [
                'as'   => 'sales.customer.remove-from-basket',
                'uses' => 'Customer\BasketController@removeBasketItemAction',
            ]
        );
        Route::get(
            'special-offers/products',
            [
                'as'   => 'offers.products',
                'uses' => 'Customer\OffersController@products',
            ]
        )->middleware(['customer']);
        Route::get(
            'special-offers/{id}/{slug}',
            [
                'as'   => 'offers.view',
                'uses' => 'Customer\OffersController@view',
            ]
        )->middleware(['customer']);

        Route::group(
            [
                'prefix' => 'checkout',
            ],
            function () {
                Route::get(
                    'address',
                    [
                        'as'   => 'sales.customer.checkout.address',
                        'uses' => 'Customer\CheckoutController@addressAction',
                    ]
                )->middleware(['customer', 'checkout']);
                Route::post(
                    'save-address',
                    [
                        'as'   => 'sales.customer.checkout.save-address',
                        'uses' => 'Customer\CheckoutController@saveAddressAction',
                    ]
                );
                Route::get(
                    'payment-method',
                    [
                        'as'   => 'sales.customer.checkout.choose-payment',
                        'uses' => 'Customer\CheckoutController@choosePaymentAction',
                    ]
                )->middleware(['customer', 'checkout']);
                Route::post(
                    'stripe/payment',
                    [
                        'as'   => 'sales.customer.stripe.pay',
                        'uses' => 'Customer\StripeController@payAction',
                    ]
                );
                Route::post(
                    'paypal/express-checkout',
                    [
                        'as'   => 'sales.customer.paypal.start',
                        'uses' => 'Customer\PayPalController@startAction',
                    ]
                );
                Route::get(
                    'paypal/return',
                    [
                        'as'   => 'sales.customer.paypal.return',
                        'uses' => 'Customer\PayPalController@returnAction',
                    ]
                );
                Route::get(
                    'paypal/cancel',
                    [
                        'as'   => 'sales.customer.paypal.cancel',
                        'uses' => 'Customer\PayPalController@cancelAction',
                    ]
                );
            }
        );

        Route::group(
            [
                'prefix' => 'orders',
            ],
            function () {
                Route::get(
                    '{orderId}',
                    [
                        'as'   => 'sales.customer.order.view',
                        'uses' => 'Customer\OrderController@viewAction',
                    ]
                )->middleware(['customer', 'suggestions']);
            }
        );

        Route::group(
            [
                'prefix'     => 'staff',
                'middleware' => [
                    'auth',
                    'staff',
                    'web',
                ],
            ],
            function () {
                Route::resource('orders', 'Staff\OrderController');
                Route::resource('offers', 'Staff\OfferController');
                Route::put(
                    'offers/{id}/products',
                    [
                        'uses' => 'Staff\OfferController@putProducts',
                        'as'   => 'offers.put-products',
                    ]
                );
                Route::put(
                    'products/{id}/offers',
                    [
                        'uses' => 'Staff\OfferController@putProductOffers',
                        'as'   => 'product.put-offers',
                    ]
                );
            }
        );
    }
);
