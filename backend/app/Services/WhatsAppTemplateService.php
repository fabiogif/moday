<?php

namespace App\Services;

class WhatsAppTemplateService
{
    public static function generateMessage(\App\Models\Event $event, \App\Models\Client $client, string $tenantName): string
    {
        return match ($event->type) {
            'promocao' => self::generatePromocaoMessage($event, $client, $tenantName),
            'aviso' => self::generateAvisoMessage($event, $client, $tenantName),
            'outro' => self::generateOutroMessage($event, $client, $tenantName),
            default => self::generateOutroMessage($event, $client, $tenantName),
        };
    }

    private static function generatePromocaoMessage(\App\Models\Event $event, \App\Models\Client $client, string $tenantName): string
    {
        $data = $event->start_date->format('d/m/Y');
        $hora = $event->start_date->format('H:i');
        $duracao = $event->duration_minutes;
        $local = $event->location ? "\n📍 *Local:* {$event->location}" : '';

        return <<<MSG
🎉 *PROMOÇÃO ESPECIAL!* 🎉

Olá, *{$client->name}*! 👋

🔥 *{$event->title}*

{$event->description}

📋 *Detalhes:*
📅 Data: {$data}
⏰ Horário: {$hora}
⏱️ Duração: {$duracao} minutos{$local}

💰 *Não perca esta oportunidade!*

Esta é uma promoção exclusiva para você!

---
_{$tenantName}_
MSG;
    }

    private static function generateAvisoMessage(\App\Models\Event $event, \App\Models\Client $client, string $tenantName): string
    {
        $data = $event->start_date->format('d/m/Y');
        $hora = $event->start_date->format('H:i');
        $duracao = $event->duration_minutes;
        $local = $event->location ? "\n📍 *Local:* {$event->location}" : '';

        return <<<MSG
📢 *AVISO IMPORTANTE* ⚠️

Olá, *{$client->name}*! 👋

*{$event->title}*

{$event->description}

📋 *Informações:*
📅 Data: {$data}
⏰ Horário: {$hora}
⏱️ Duração: {$duracao} minutos{$local}

📱 *Mantenha-se informado!*

---
_{$tenantName}_
MSG;
    }

    private static function generateOutroMessage(\App\Models\Event $event, \App\Models\Client $client, string $tenantName): string
    {
        $data = $event->start_date->format('d/m/Y');
        $hora = $event->start_date->format('H:i');
        $duracao = $event->duration_minutes;
        $local = $event->location ? "\n📍 *Local:* {$event->location}" : '';

        return <<<MSG
📅 *NOVO EVENTO*

Olá, *{$client->name}*! 👋

*{$event->title}*

{$event->description}

📋 *Detalhes:*
📅 Data: {$data}
⏰ Horário: {$hora}
⏱️ Duração: {$duracao} minutos{$local}

ℹ️ Anote essa data em sua agenda!

---
_{$tenantName}_
MSG;
    }

    public static function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($phone) === 11 || strlen($phone) === 10) {
            $phone = '55' . $phone;
        }

        return $phone;
    }

    public static function generateWhatsAppLink(string $phone, string $message): string
    {
        $formattedPhone = self::formatPhoneNumber($phone);
        $encodedMessage = urlencode($message);

        return "https://api.whatsapp.com/send?phone={$formattedPhone}&text={$encodedMessage}";
    }
}
