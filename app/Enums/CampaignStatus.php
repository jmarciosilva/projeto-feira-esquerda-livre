<?php

namespace App\Enums;

enum CampaignStatus: string
{
    case Draft     = 'draft';
    case Scheduled = 'scheduled';
    case Sending   = 'sending';
    case Sent      = 'sent';
    case Failed    = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Rascunho',
            self::Scheduled => 'Agendado',
            self::Sending   => 'Enviando',
            self::Sent      => 'Enviado',
            self::Failed    => 'Com erros',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft     => 'gray',
            self::Scheduled => 'blue',
            self::Sending   => 'yellow',
            self::Sent      => 'green',
            self::Failed    => 'red',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    public function isDeletable(): bool
    {
        return $this === self::Draft;
    }
}
