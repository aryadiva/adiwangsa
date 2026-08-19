<?php

use App\Support\WeightValidation;

it('reports a set is full only when it already reaches exactly 100', function () {
    expect(WeightValidation::isFull(100.0))->toBeTrue()
        ->and(WeightValidation::isFull(99.99))->toBeFalse()
        ->and(WeightValidation::isFull(100.01))->toBeFalse()
        ->and(WeightValidation::isFull(0.0))->toBeFalse()
        ->and(WeightValidation::isFull(60.0))->toBeFalse();
});

it('allows adding partial weights that do not exceed 100', function () {
    expect(WeightValidation::canAdd(0.0, 20.0))->toBeTrue()      // first milestone at 20%
        ->and(WeightValidation::canAdd(20.0, 10.0))->toBeTrue()  // add 10% -> 30%
        ->and(WeightValidation::canAdd(60.0, 40.0))->toBeTrue()  // complete -> 100%
        ->and(WeightValidation::canAdd(0.0, 100.0))->toBeTrue(); // single row at 100%
});

it('rejects a weight that pushes the set past 100', function () {
    expect(WeightValidation::canAdd(60.0, 50.0))->toBeFalse()   // 110%
        ->and(WeightValidation::canAdd(100.0, 1.0))->toBeFalse()
        ->and(WeightValidation::canAdd(90.0, 10.01))->toBeFalse();
});
