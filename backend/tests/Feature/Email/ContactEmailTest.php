<?php

namespace Tests\Feature\Email;

use App\Mail\ContactFormMail;
use App\Services\ContactService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactEmailTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(): array
    {
        return [
            'firstName' => 'João',
            'lastName' => 'Silva',
            'email' => 'joao@example.com',
            'subject' => 'Dúvida sobre planos',
            'message' => 'Gostaria de saber mais sobre os planos disponíveis.',
        ];
    }

    public function test_contact_service_sends_email_to_configured_address(): void
    {
        Mail::fake();

        config([
            'mail.contact_to' => 'contato@albatec.com.br',
        ]);

        app(ContactService::class)->send($this->validPayload());

        Mail::assertSent(ContactFormMail::class, function (ContactFormMail $mail) {
            return $mail->hasTo('contato@albatec.com.br')
                && $mail->email === 'joao@example.com'
                && $mail->fullName === 'João Silva'
                && $mail->subjectText === 'Dúvida sobre planos';
        });
    }

    public function test_contact_form_mail_has_reply_to_visitor(): void
    {
        $mail = new ContactFormMail(
            'João Silva',
            'joao@example.com',
            'Assunto teste',
            'Corpo da mensagem.',
        );

        $envelope = $mail->envelope();

        $this->assertCount(1, $envelope->replyTo);
        $this->assertEquals('joao@example.com', $envelope->replyTo[0]->address);
        $this->assertEquals('João Silva', $envelope->replyTo[0]->name);
        $this->assertStringContainsString('Assunto teste', $envelope->subject);
    }

    public function test_contact_api_sends_email_successfully(): void
    {
        Mail::fake();

        config(['mail.contact_to' => 'contato@albatec.com.br']);

        $response = $this->postJson('/api/contact', $this->validPayload());

        $response->assertOk()
            ->assertJsonPath('success', true);

        Mail::assertSent(ContactFormMail::class);
    }

    public function test_contact_api_validates_required_fields(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/contact', []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        Mail::assertNothingSent();
    }

    public function test_contact_api_validates_email_format(): void
    {
        Mail::fake();

        $payload = $this->validPayload();
        $payload['email'] = 'email-invalido';

        $response = $this->postJson('/api/contact', $payload);

        $response->assertStatus(422);
        Mail::assertNothingSent();
    }
}
