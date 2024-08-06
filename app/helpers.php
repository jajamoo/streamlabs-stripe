<?php

if (!function_exists('writeNewEnvironmentFileWithClock')) {
    function writeNewEnvironmentFileWithClock($clockId): bool
    {
        $replaced = preg_replace(
            clockReplacementPattern(),
            'STRIPE_TEST_CLOCK='.$clockId,
            $input = file_get_contents(app()->environmentFilePath())
        );

        if ($replaced === $input || $replaced === null) {
            throw new Exception('Unable to set application key. No STRIPE_TEST_CLOCK variable was found in the .env file.');
        }

        file_put_contents(app()->environmentFilePath(), $replaced);

        return true;
    }
}

if (!function_exists('clockReplacementPattern')) {
    function clockReplacementPattern(): string
    {
        $escaped = preg_quote('='.env('STRIPE_TEST_CLOCK'), '/');

        return "/^STRIPE_TEST_CLOCK{$escaped}/m";
    }
}
