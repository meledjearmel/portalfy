<?php

if (! function_exists('dev_admin_mail')) {
    function dev_admin_mail(): ?string
    {
        if (app()->environment(['local', 'testing'])) {
            return 'admin@portalfy.test';
        }

        return '';
    }
}

if (! function_exists('dev_customer_mail')) {
    function dev_customer_mail(): ?string
    {
        if (app()->environment(['local', 'testing'])) {
            return 'client@portalfy.test';
        }

        return '';
    }
}

if (! function_exists('dev_password')) {
    function dev_password(): ?string
    {
        if (app()->environment(['local', 'testing'])) {
            return 'password';
        }

        return '';
    }
}
