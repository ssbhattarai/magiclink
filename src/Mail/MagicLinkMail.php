<?php

namespace Ssbhattarai\MagicLink\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MagicLinkMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public string $link) {}

    public function build()
    {
        return $this->subject(config('magiclink.email_subject'))
            ->view('magiclink::email')
            ->with(['link' => $this->link]);
    }
}
