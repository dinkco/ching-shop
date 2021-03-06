<?php

namespace ChingShop\Validation;

/**
 * Interface ValidationInterface.
 */
interface ValidationInterface
{
    /**
     * @param array $testData
     * @param array $rules
     *
     * @return bool
     */
    public function passes(array $testData, array $rules): bool;

    /**
     * @return array
     */
    public function messages(): array;
}
