<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://cdn.streamlabs.com/static/imgs/identity/streamlabs-logo-thumb.png" alt="Streamlabs Kevin"></a></p>


# Streamlabs Senior Payments Assignment




## Setup and Notes
- (Optional) Laravel Sail can be used for this project. You can find the documentation [here](https://laravel.com/docs/11.x/sail)
  - copy .env.example to .env
  - NB: in the /docker/Dockerfile, change the arm deb files to amd or the installation will fail.
    - `RUN curl -L -o /tmp/stripe_1.21.2_linux_amd64.deb https://github.com/stripe/stripe-cli/releases/download/v1.21.2/stripe_1.21.2_linux_amd64.deb \
      && dpkg -i /tmp/stripe_1.21.2_linux_amd64.deb`
  - install composer dependencies with `docker run --rm --interactive --tty --name tmp-composer-install --volume $PWD:/app composer install --ignore-platform-reqs --no-scripts`
  - get the app going with `vendor/bin/sail up -d`
  - run `vendor/bin/sail key:generate`
    - This is actually `vendor/bin/sail php artisan key:generate`
  - visit `localhost` in your browser to see the app running
  - Go to the Stripe Dashboard and create a simulation here `https://dashboard.stripe.com/test/billing/subscriptions/test-clocks` And add the ID of the clock to the .env file under the STRIPE_TEST_CLOCK param
  - There are a few ways to test this code:
    - You can either use the endpoints
      - `localhost/api/create`
        - Creates the customer and subscription with the specified parameters
      - `localhost/api/check`
        - Checks to see if 5 months have passed in the current subscription, and if it has, do a prorated upgrade to the premium
    - Or the commands
      - In the project Root

>  * Note: I know I could have used the advance test clock API endpoints outlined here: https://docs.stripe.com/api/test_clocks/advance \
>   But in the interest of time, I advanced the time via the Stripe Dashboard 
>  * For the prorated upgrade to work, once the initial customer and subscription are set up, go to the Stripe Dashboard, advance the time associated with 

## Documentation & Thought Process
The code is to be published on a public github repository for our team to access. Make sure that we can see your progress in your commit history, a single commit is not enough.

**Please include a README.md file that includes the following information:**

- A screenshot of your final output
- Instructions on how to run your code and any tests
