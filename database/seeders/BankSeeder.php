<?php

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banks = [
            [
                'code' => '001',
                'name' => 'Banco do Brasil',
                'full_name' => 'Banco do Brasil S.A.',
                'ispb' => '00000000',
                'supports_pix' => true,
            ],
            [
                'code' => '237',
                'name' => 'Bradesco',
                'full_name' => 'Banco Bradesco S.A.',
                'ispb' => '60746948',
                'supports_pix' => true,
            ],
            [
                'code' => '341',
                'name' => 'Itaú',
                'full_name' => 'Itaú Unibanco S.A.',
                'ispb' => '60701190',
                'supports_pix' => true,
            ],
            [
                'code' => '104',
                'name' => 'Caixa',
                'full_name' => 'Caixa Econômica Federal',
                'ispb' => '00360305',
                'supports_pix' => true,
            ],
            [
                'code' => '033',
                'name' => 'Santander',
                'full_name' => 'Banco Santander Brasil S.A.',
                'ispb' => '90400888',
                'supports_pix' => true,
            ],
            [
                'code' => '260',
                'name' => 'Nubank',
                'full_name' => 'Nu Pagamentos S.A.',
                'ispb' => '18236120',
                'supports_pix' => true,
            ],
            [
                'code' => '077',
                'name' => 'Inter',
                'full_name' => 'Banco Inter S.A.',
                'ispb' => '00416968',
                'supports_pix' => true,
            ],
            [
                'code' => '290',
                'name' => 'Pagseguro',
                'full_name' => 'PagSeguro Internet S.A.',
                'ispb' => '08561701',
                'supports_pix' => true,
            ],
            [
                'code' => '323',
                'name' => 'Mercado Pago',
                'full_name' => 'Mercado Pago',
                'ispb' => '10573521',
                'supports_pix' => true,
            ],
            [
                'code' => '197',
                'name' => 'Stone',
                'full_name' => 'Stone Pagamentos S.A.',
                'ispb' => '16501555',
                'supports_pix' => true,
            ],
            [
                'code' => '212',
                'name' => 'Banco Original',
                'full_name' => 'Banco Original S.A.',
                'ispb' => '92894922',
                'supports_pix' => true,
            ],
            [
                'code' => '756',
                'name' => 'Bancoob',
                'full_name' => 'Banco Cooperativo do Brasil S.A.',
                'ispb' => '02038232',
                'supports_pix' => true,
            ],
            [
                'code' => '748',
                'name' => 'Sicredi',
                'full_name' => 'Banco Cooperativo Sicredi S.A.',
                'ispb' => '01181521',
                'supports_pix' => true,
            ],
            [
                'code' => '655',
                'name' => 'Neon',
                'full_name' => 'Neon Pagamentos S.A.',
                'ispb' => '20855875',
                'supports_pix' => true,
            ],
            [
                'code' => '380',
                'name' => 'PicPay',
                'full_name' => 'PicPay Servicos S.A.',
                'ispb' => '22896431',
                'supports_pix' => true,
            ],
        ];

        foreach ($banks as $bank) {
            Bank::updateOrCreate(
                ['code' => $bank['code']],
                $bank
            );
        }

        $this->command->info('✅ ' . count($banks) . ' bancos cadastrados com sucesso!');
    }
}

