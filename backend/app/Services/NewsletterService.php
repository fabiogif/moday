<?php

namespace App\Services;

use App\Models\NewsletterSubscriber;
use Illuminate\Support\Collection;

class NewsletterService
{
    public function subscribe(string $email): NewsletterSubscriber
    {
        $normalizedEmail = strtolower(trim($email));

        $subscriber = NewsletterSubscriber::query()
            ->where('email', $normalizedEmail)
            ->first();

        if ($subscriber) {
            if ($subscriber->unsubscribed_at !== null) {
                $subscriber->update([
                    'subscribed_at' => now(),
                    'unsubscribed_at' => null,
                ]);
            }

            return $subscriber->fresh();
        }

        return NewsletterSubscriber::create([
            'email' => $normalizedEmail,
            'subscribed_at' => now(),
        ]);
    }

    public function getActiveSubscribers(): Collection
    {
        return NewsletterSubscriber::query()
            ->active()
            ->orderByDesc('subscribed_at')
            ->get();
    }

    public function getStats(): array
    {
        $active = NewsletterSubscriber::query()->active()->count();
        $total = NewsletterSubscriber::query()->count();

        return [
            'active' => $active,
            'total' => $total,
            'unsubscribed' => max(0, $total - $active),
        ];
    }
}
