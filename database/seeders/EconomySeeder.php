<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PaymentPackage;
use App\Models\WalletType;
use Illuminate\Database\Seeder;

/**
 * Ekonomi başlangıç verisi — docs/DATABASE.md §12, OD-03 varsayılanı: yalnızca `web_credit` (Meridyen Kredisi, MK).
 * Paket fiyatları örnektir; ACP → Ekonomi → Paketler'den değiştirilir.
 */
final class EconomySeeder extends Seeder
{
    public function run(): void
    {
        $type = WalletType::query()->updateOrCreate(['key' => WalletType::WEB_CREDIT], [
            'name' => 'Meridyen Kredisi', 'unit_label' => 'MK', 'is_purchasable' => true, 'is_active' => true, 'sort' => 0,
        ]);

        foreach ([
            ['Başlangıç', 100, 0, 4999, 1],
            ['Standart', 250, 25, 11999, 2],
            ['Destekçi', 600, 100, 24999, 3],
        ] as [$name, $credit, $bonus, $price, $sort]) {
            PaymentPackage::query()->updateOrCreate(['wallet_type_id' => $type->id, 'name' => $name], [
                'credit_amount' => $credit, 'bonus_amount' => $bonus, 'price_minor' => $price, 'currency' => 'TRY', 'is_active' => true, 'sort' => $sort,
            ]);
        }
    }
}
