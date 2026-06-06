<?php
/**
 * แสดงสถานะ SLA บน UI (เทียบกับเวลาปัจจุบัน)
 */
declare(strict_types=1);

namespace App\Helpers;

final class SlaUi
{
    /**
     * @return array{label: string, badgeClass: string, dotClass: string}
     */
    public static function badge(?string $slaDueAt): array
    {
        if ($slaDueAt === null || $slaDueAt === '') {
            return [
                'label' => '—',
                'badgeClass' => 'bg-slate-100 text-slate-600 ring-slate-200',
                'dotClass' => 'bg-slate-400',
            ];
        }
        $ts = strtotime($slaDueAt);
        if ($ts === false) {
            return [
                'label' => '—',
                'badgeClass' => 'bg-slate-100 text-slate-600 ring-slate-200',
                'dotClass' => 'bg-slate-400',
            ];
        }
        $now = time();
        if ($ts < $now) {
            return [
                'label' => 'เกิน SLA',
                'badgeClass' => 'bg-red-100 text-red-800 ring-red-200',
                'dotClass' => 'bg-red-600',
            ];
        }
        $diff = $ts - $now;
        if ($diff <= 120) {
            return [
                'label' => 'ใกล้หมด',
                'badgeClass' => 'bg-amber-100 text-amber-900 ring-amber-200',
                'dotClass' => 'bg-amber-500',
            ];
        }
        return [
            'label' => 'ตาม SLA',
            'badgeClass' => 'bg-emerald-50 text-emerald-800 ring-emerald-100',
            'dotClass' => 'bg-emerald-500',
        ];
    }
}
