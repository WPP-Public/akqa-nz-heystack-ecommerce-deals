# Ecommerce Deals

[![build status](https://gitlab-ci.heyday.net.nz/projects/6/status.png?ref=master)](https://gitlab-ci.heyday.net.nz/projects/6?ref=master)

## Architecture

The Deals system works off the concept of Conditions and Results. Each deal is made from one or more conditions and one result.

### Conditions

#### Start date (`StartDate`)

Allows a deal to only be available after a certain date

#### End date (`EndDate`)

Allows a deal to only be available before a certain date

#### Start and end date combined

Allows a deal to only be available after and before certain dates

#### Has coupon (`HasCoupon`)

Allows a deal to only be available when a coupon is used

#### Minimum cart total (`MinimumCartTotal`)

Allows a deal to only be available when the total cart spend is greater than or equal to specified amount

#### Specific purchasables must have specific quanity in cart (`PurchasableHasQuantityInCart`)

Allows a deal to only be available when specific quantities of specific purchasables are in the cart.

This conditions can be met multiple times.

e.g. if configured to be available when 5 pears are bought, then the condition will only be met when there are 5 or more
pears in the cart.

e.g. if configured to be available when 2 pears are bought, then the condition will only be met 2 times when there are exactly 4
pears in the cart.

#### Specific purchasables must have combined quantity in cart (`QuantityOfPurchasablesInCart`)

Allows a deal to only be available when the combined quantity of allowed purchasables are greater than or equal to the configured amount

e.g. if configured to be available when at least 5 of (pears + apples) are bought, then the condition will only be met
when there are at least 5 of the combined total of apples and pears in the cart.

### Results

#### Cart discount (`CartDiscount`)

Allows a percentage or absolute amount to be deducted from the combined purchasables total.

e.g. if set to $30 and the combined purchasables total would usually be $100 the new combined purchasables total will be $70
e.g. if set to 50% and the combined purchasables total would usually be $100 the new combined purchasables total will be $50

#### Cheapeast purchasable free

The "Cheapest purchasable" is calculated using the "unit price" of each purchasable in the currently configured currency.

This result will deduct off the amount of the "current" cheapest purchasable as many times
as the results conditions were met. (see `PurchasableHasQuantityInCart`). This result will run at the priority level of
`100`, this means that it will run first unless there is another result (or condition) with a higher priority. The
purpose of this is becaause we want to "make" the relevant purchasables free prior to other discounts being applied. e.g.
if this result is configured in conjunction with a `CartDiscount`, we don't want to cart discount to be applied until
after this result is applied.

#### Free gift

Add a specified free gift to the cart

#### Purchasable/s discount

Allows a percentage or absolute amount to be deducted off specified (or all) purchasables.

#### Discount off shipping

Allows a percentage or absolute amount to be deducted off the shipping total
