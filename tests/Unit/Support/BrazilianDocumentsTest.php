<?php

namespace Tests\Unit\Support;

use App\Support\BrazilianDocuments;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BrazilianDocumentsTest extends TestCase
{
    #[Test]
    public function valida_cpf_correto(): void
    {
        $this->assertTrue(BrazilianDocuments::isValidCpf('529.982.247-25'));
    }

    #[Test]
    public function rejeita_cpf_invalido(): void
    {
        $this->assertFalse(BrazilianDocuments::isValidCpf('111.111.111-11'));
        $this->assertFalse(BrazilianDocuments::isValidCpf('123'));
    }

    #[Test]
    public function valida_cnpj_numerico_correto(): void
    {
        $this->assertTrue(BrazilianDocuments::isValidCnpj('11.222.333/0001-81'));
    }

    #[Test]
    public function valida_cnpj_alfanumerico_correto(): void
    {
        // 12.ABC.345/01DE-35 (sem formatação: 12ABC34501DE35)
        // Cálculo DV1: charVal de cada posição × peso
        // 1×5 + 2×4 + 17×3 + 18×2 + 19×9 + 3×8 + 4×7 + 5×6 + 0×5 + 1×4 + 20×3 + 21×2
        // = 5 + 8 + 51 + 36 + 171 + 24 + 28 + 30 + 0 + 4 + 60 + 42 = 459
        // 459 % 11 = 8 → DV1 = 11 - 8 = 3
        // DV2: inclui DV1 (3) com peso [6,5,4,3,2,9,8,7,6,5,4,3,2]
        // 1×6 + 2×5 + 17×4 + 18×3 + 19×2 + 3×9 + 4×8 + 5×7 + 0×6 + 1×5 + 20×4 + 21×3 + 3×2
        // = 6 + 10 + 68 + 54 + 38 + 27 + 32 + 35 + 0 + 5 + 80 + 63 + 6 = 424
        // 424 % 11 = 6 → DV2 = 11 - 6 = 5
        $this->assertTrue(BrazilianDocuments::isValidCnpj('12.ABC.345/01DE-35'));
        $this->assertTrue(BrazilianDocuments::isValidCnpj('12ABC34501DE35'));
    }

    #[Test]
    public function rejeita_cnpj_invalido(): void
    {
        $this->assertFalse(BrazilianDocuments::isValidCnpj('11.111.111/1111-11'));
        $this->assertFalse(BrazilianDocuments::isValidCnpj('123'));
    }

    #[Test]
    public function rejeita_cnpj_com_letras_no_dv(): void
    {
        $this->assertFalse(BrazilianDocuments::isValidCnpj('12ABC34501DEAB'));
    }

    #[Test]
    public function converte_alphanumeric_para_maiusculo(): void
    {
        $this->assertSame('12ABC34501DE35', BrazilianDocuments::onlyAlphanumeric('12.abc.345/01de-35'));
    }
}
