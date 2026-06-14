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
    public function valida_cnpj_correto(): void
    {
        $this->assertTrue(BrazilianDocuments::isValidCnpj('11.222.333/0001-81'));
    }

    #[Test]
    public function rejeita_cnpj_invalido(): void
    {
        $this->assertFalse(BrazilianDocuments::isValidCnpj('11.111.111/1111-11'));
        $this->assertFalse(BrazilianDocuments::isValidCnpj('123'));
    }
}
