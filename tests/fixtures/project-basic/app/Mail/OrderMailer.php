<?php

namespace App\Mail;

class OrderMailer
{
    public function send()
    {
        $message = new \Swift_Message('Subject');
        return $message;
    }
}
