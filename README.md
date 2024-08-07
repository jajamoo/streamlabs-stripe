<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://cdn.streamlabs.com/static/imgs/identity/streamlabs-logo-thumb.png" alt="Streamlabs Kevin"></a></p>

# Streamlabs Senior Payments Assignment
## Setup and Notes
- I used Laravel Sail to get this project up and Running. You can find the documentation [here](https://laravel.com/docs/11.x/sail)
  - copy .env.example to .env
  - NB: in the /docker/Dockerfile, change the arm deb files to amd or the installation will fail.
    - `RUN curl -L -o /tmp/stripe_1.21.2_linux_amd64.deb https://github.com/stripe/stripe-cli/releases/download/v1.21.2/stripe_1.21.2_linux_amd64.deb \
      && dpkg -i /tmp/stripe_1.21.2_linux_amd64.deb`
  - install composer dependencies with `docker run --rm --interactive --tty --name tmp-composer-install --volume $PWD:/app composer install --ignore-platform-reqs --no-scripts`
  - get the app going with `vendor/bin/sail up -d`
  - Run `vendor/bin/sail php artisan key:generate`
  - If you're building from scratch (or you don't see a `routes/api.php` file, you'll need an API route to bypass Laravel's web route's inherent CSRF protections in `routes/web.api`
    - Run `vendor/bin/sail php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`
    - Run `vendor/bin/sail php artisan install:api`
  - visit `localhost` in your browser to see the app running
  - Go to the Stripe Dashboard and create a simulation here `https://dashboard.stripe.com/test/billing/subscriptions/test-clocks` And add the ID of the clock to the .env file under the STRIPE_TEST_CLOCK param
  - Testing the code:
    - You use endpoints:
      - `localhost/api/subscribe` : json parameters `{'name': 'a_name_here', 'email':'some_email_here'}`
        - Creates the customer, attach a payment method and a subscription with the specified parameters.
        - If successful, you will get a subscription ID
        - Plug that subscription ID into the next endpoint 
      - `localhost/api/check` : json parameters `{'subscription_id': 'sub_xxxxx', 'skip_month_check':'true_or_false'}`
        - Checks to see if 5 months have passed in the current subscription, and if it has, do a prorated upgrade to the premium. If you want to skip the 5 month check, pass in true for the skip_month_check in the JSON request, otherwise pass in a 'false' boolean for skip_month_check to actually check for the 5 months
        - If successful, you'll get a `{"success": true}`
        - Go to the Dashboard and confirm your newly prorated subscription :) 


>  * Note: I know I could have used the advance test clock API endpoints outlined here: https://docs.stripe.com/api/test_clocks/advance \
>   But in the interest of time, I advanced the time via the Stripe Dashboard 
>  * For the prorated upgrade to work, once the initial customer and subscription are set up, go to the Stripe Dashboard, advance the time associated with the customer. Otherwise, pass in the boolean referenced above in the controller via the skip_month_check param in the JSON request

## Documentation & Improvements
A few things to note as we look at this code:
- Possible improvements:
  - When I look for the price item, I don't think the foreach() to grab the products is necessary, since price has a lookup_key
  - Maybe I can attribute this to misunderstanding the requirements or an 'OOPS I SHOULD HAVE...' moment but I'd like to address the Clocks part of this assignment
    - The Stripe Clocks simulations were really cool to see the behavior of subscriptions as they move through time
      I think that instead of using the dashboard to simulate the time, I could have done it programatically - perhaps a separate service that dealt with time, advancing it using the test_clocks API
  - Due to the time constraints, I wasn't able to figure out a better solution for the 5 month proration. Currently, we have to manually advance time in the Dashboard for the test clock's simulation
    (an improvement that could have been done programatically, as I brought up in the point above) and then prorate
    - A better approach would have been to set the proration on the subscription to fire off at some point in the future: so when it hits the 5 month mark, it automatically prorates
      I did not find a way to do that but would love to discuss with the team an approach to do it, if possible
    
## The Proof is in the Pudding (or in this case, screenshots)!
  - [Customer Created](https://i.imgur.com/kb8wuei.png)
  - [Programatic creation of the Customer and Subscription](https://i.imgur.com/RN4AgiU.png)
  - [Proration Upgrade on the 5th month](https://i.imgur.com/KRPfqGg.png)
  - [Output of Table data]()