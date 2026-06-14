<?php

namespace App\Services;

use App\Mail\ContactFormMail;
use Illuminate\Support\Facades\Mail;

class ContactService
{
    public function send(array $data): void
    {
        $fullName = $data['firstName'].' '.$data['lastName'];

        Mail::to(config('mail.contact_to'))
            ->send(new ContactFormMail(
                $fullName,
                $data['email'],
                $data['subject'],
                $data['message'],
            ));
    }
}
