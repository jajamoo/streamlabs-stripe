<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://cdn.streamlabs.com/static/imgs/identity/streamlabs-logo-thumb.png" alt="Streamlabs Kevin"></a></p>


Thank you for choosing to invest your time in this assignment.  We recognize it’s difficult to find the time to complete a coding assignment, and we value your time and investment in this process with us.
# Streamlabs Senior Payments Assignment

You've been tasked with creating an analysis for how subscriptions behave over the course of a year and the expected revenue from those subscriptions.

You will be assessing the estimated revenue for 3 different subscription products. You've been provided seed data for 3 different products, 2 existing customers and their subscriptions.

## Requirements
- Return a table for each product that lists out a subscription per row.
- The columns should be the following: customer email, product name, ...months 1-12, lifetime value for subscription. The final row should contain usd totals for each month.
- Using the Stripe API, create a new customer and subscribe them to the following:
  - Price: `${price_monthly_crossclip_basic:id}`
  - Coupon: `${coupon_5_off_3_months:id}`
  - Trial: 30 days
  - Currency: `gbp`
- For your created subscription, during the 5th month perform a mid-cycle upgrade with proration on the 15th to the following:
  - Price: `${price_monthly_crossclip_premium:id}`
- **You can display these tables via HTML or command line output.**

| Customer Email            | Product Name | {endOfMonth date} 1 | {endOfMonth} 2 | {endOfMonth} 3 | {endOfMonth} 4 | {endOfMonth} 5 | {endOfMonth} 6 | {endOfMonth} 7 | {endOfMonth} 8 | {endOfMonth} 9 | {endOfMonth} 10 | {endOfMonth} 11 | {endOfMonth} 12 | Life Time Value |
|---------------------------|--------------|---------------------|----------------|----------------|----------------|----------------|----------------|----------------|----------------|----------------|-----------------|-----------------|-----------------|-----------------|
| your.customer@example.com | Product A    | $0                  | $10            | $10            | $10            | $10            | $10            | $10            | $10            | $0             | $0              | $0              | $0              | $80             |
| john.doe@example.com      | Product A    | $10                 | $10            | $10            | $10            | $10            | $10            | $10            | $10            | $0             | $0              | $0              | $0              | $80             |
| jane.smith@example.com    | Product A    | $15                 | $15            | $15            | $15            | $15            | $15            | $15            | $15            | $15            | $15             | $15             | $15             | $180            |
| **Totals**                |              | **$25**             | **$25**        | **$25**        | **$25**        | **$25**        | **$25**        | **$25**        | **$25**        | **$15**        | **$15**         | **$15**         | **$15**         | **$260**        |


| Customer Email       | Product Name | {endOfMonth} 1 | {endOfMonth} 2 | {endOfMonth} 3 | {endOfMonth} 4 | {endOfMonth} 5 | {endOfMonth} 6 | {endOfMonth} 7 | {endOfMonth} 8 | {endOfMonth} 9 | {endOfMonth} 10 | {endOfMonth} 11 | {endOfMonth} 12 | Life Time Value |
|----------------------|--------------|----------------|----------------|----------------|----------------|----------------|----------------|----------------|----------------|----------------|-----------------|-----------------|-----------------|-----------------|
| john.doe@example.com | Product B    | $10            | $10            | $10            | $10            | $10            | $10            | $10            | $10            | $0             | $0              | $0              | $0              | $80             |
| jane.smith@example.com | Product B    | $15            | $15            | $15            | $15            | $15            | $15            | $15            | $15            | $15            | $15             | $15             | $15             | $180            |
| **Totals**           |              | **$25**        | **$25**        | **$25**        | **$25**        | **$25**        | **$25**        | **$25**        | **$25**        | **$15**        | **$15**         | **$15**         | **$15**         | **$260**        |


| Customer Email       | Product Name | {endOfMonth} 1 | {endOfMonth} 2 | {endOfMonth} 3 | {endOfMonth} 4 | {endOfMonth} 5 | {endOfMonth} 6 | {endOfMonth} 7 | {endOfMonth} 8 | {endOfMonth} 9 | {endOfMonth} 10 | {endOfMonth} 11 | {endOfMonth} 12 | Life Time Value |
|----------------------|--------------|----------------|----------------|----------------|----------------|----------------|----------------|----------------|----------------|----------------|-----------------|-----------------|-----------------|-----------------|
| john.doe@example.com | Product C    | $10            | $10            | $10            | $10            | $10            | $10            | $10            | $10            | $0             | $0              | $0              | $0              | $80             |
| jane.smith@example.com | Product C    | $15            | $15            | $15            | $15            | $15            | $15            | $15            | $15            | $15            | $15             | $15             | $15             | $180            |
| **Totals**           |              | **$25**        | **$25**        | **$25**        | **$25**        | **$25**        | **$25**        | **$25**        | **$25**        | **$15**        | **$15**         | **$15**         | **$15**         | **$260**        |


## Guiding Philosophy

The assignment should take about 4 hours to complete. We want to see a well-modeled, working solution that shows that you can write code and read directions.

We are NOT

- going to throw any "gotchas" at you or your submission,
- testing for your ability to suss out edge cases, or
- trying to trick you.

Keep it simple! Please do NOT implement extra features that we don't ask for.

## Stack
At Streamlabs we mainly make use of PHP, Laravel, Vue, React, TypeScript, MySQL. Please make use of Laravel & PHP for this assignment so we know you are familiar with our backend stack.

## Prerequisites

### Stripe
You will be heavily working with stripe for this assignment. You will need to create a stripe account.

Stripe Concepts:
- [Stripe CLI](https://docs.stripe.com/cli)
- [Stripe Fixtures](https://docs.stripe.com/cli/fixtures)
- [Stripe Test Clocks and Simulations](https://docs.stripe.com/billing/testing/test-clocks/api-advanced-usage)
- [Stripe API - Customers, Subscriptions, Products, Prices, and Coupons](https://docs.stripe.com/api?lang=php)

## Getting Started
- (Optional) Laravel Sail can be used for this project. You can find the documentation [here](https://laravel.com/docs/11.x/sail)
  - copy .env.example to .env
  - install composer dependencies with `docker run --rm --interactive --tty --name tmp-composer-install --volume $PWD:/app composer install --ignore-platform-reqs --no-scripts`
  - get the app going with `vendor/bin/sail up -d`
  - run `vendor/bin/sail key:generate`
  - visit `localhost` in your browser to see the app running
  - includes stripe cli 
- Create a new stripe account
- Use the stripe CLI to login and authorize to newly created account
- Create a [stripe test clock](https://dashboard.stripe.com/test/billing/subscriptions/test-clocks) to simulate time
- Once you've added your test clock you can use stripe cli to run your fixture. Fixture is located at fixtures/seed.json
  - The fixture will populate data into your stripe account

> **Warning:** You'll likely have to create multiple test clocks and seed data multiple times. This is normal and expected. While all data will be constrained by each test clock you can delete all stripe data by going to the [Stripe Developers](https://dashboard.stripe.com/test/developers) page and selecting "Delete all test data" option at the bottom of the page.

## Documentation & Thought Process
The code is to be published on a public github repository for our team to access. Make sure that we can see your progress in your commit history, a single commit is not enough.

**Please include a README.md file that includes the following information:**

- A screenshot of your final output
- Instructions on how to run your code and any tests
