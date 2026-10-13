<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class RecoverProductsFailed extends Mailable
{
    /**
     * @param  array<string, mixed>  $options  Opções usadas na execução do comando
     * @param  array<int, string>  $errors  Mensagens de erro coletadas durante a execução
     */
    public function __construct(
        public array $options,
        public array $errors,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Erro na geração de placas (products:recover) - ' . now()->format('d/m/Y H:i'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.recover-products-failed');
    }
}
