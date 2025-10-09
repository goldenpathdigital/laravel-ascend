<?php

declare(strict_types=1);

return [
    'default' => env('MAIL_MAILER', 'smtp'),
    'swift_transport' => 'SwiftMailer',
];
