<?php

namespace App\Constants;

class LogBookConst
{
    const STATUS_DRAFT = 'draft';

    const STATUS_IN_PROGRESS = 'in_progress';

    const STATUS_DONE = 'done';

    /**
     * Status opsi untuk filter dan form log book.
     *
     * @return array<string, string>
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_IN_PROGRESS => 'Berjalan',
            self::STATUS_DONE => 'Selesai',
        ];
    }
}
