<?php

namespace Illuminate\Tests\Integration\Support;

use Illuminate\Support\Pluralizer;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;

class PluralizerPortugueseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Pluralizer::useLanguage('portuguese');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Pluralizer::useLanguage('english');
    }

    public function testBasicSingular()
    {
        $this->assertSame('herói', Str::singular('heróis'));
        $this->assertSame('irmão', Str::singular('irmãos'));
        $this->assertSame('chafariz', Str::singular('chafarizes'));
        $this->assertSame('colher', Str::singular('colheres'));
        $this->assertSame('modelo', Str::singular('modelos'));
        $this->assertSame('venda', Str::singular('vendas'));
        $this->assertSame('usuário', Str::singular('usuários'));
        $this->assertSame('comissão', Str::singular('comissões'));
    }

    public function testIrregulars()
    {
        $this->assertSame('males', Str::plural('mal'));
        $this->assertSame('lápis', Str::singular('lápis'));
    }

    public function testBasicPlural()
    {
        $this->assertSame('fênix', Str::plural('fênix'));
        $this->assertSame('palavras', Str::plural('palavra'));
        $this->assertSame('modelos', Str::plural('modelo'));
        $this->assertSame('vendas', Str::plural('venda'));
        $this->assertSame('usuários', Str::plural('usuário'));
        $this->assertSame('comissões', Str::plural('comissão'));
    }

    public function testCaseSensitiveSingularUsage()
    {
        $this->assertSame('Criança', Str::singular('Crianças'));
        $this->assertSame('CIDADÃO', Str::singular('CIDADÃOS'));
    }

    public function testCaseSensitiveSingularPlural()
    {
        $this->assertSame('Crianças', Str::plural('Criança'));
        $this->assertSame('CIDADÃOS', Str::plural('CIDADÃO'));
        $this->assertSame('Testes', Str::plural('Teste'));
    }

    public function testPluralAppliedForStringEndingWithNumericCharacter()
    {
        $this->assertSame('Usuário1s', Str::plural('Usuário1'));
        $this->assertSame('Usuário2s', Str::plural('Usuário2'));
        $this->assertSame('Usuário3s', Str::plural('Usuário3'));
    }

    public function testPluralSupportsArrays()
    {
        $this->assertSame('usuários', Str::plural('usuário', []));
        $this->assertSame('usuário', Str::plural('usuário', ['um']));
        $this->assertSame('usuários', Str::plural('usuário', ['um', 'dois']));
    }

    public function testPluralSupportsCollections()
    {
        $this->assertSame('usuários', Str::plural('usuário', collect()));
        $this->assertSame('usuário', Str::plural('usuário', collect(['um'])));
        $this->assertSame('usuários', Str::plural('usuário', collect(['um', 'dois'])));
    }

    public function testPluralStudlySupportsArrays()
    {
        $this->assertPluralStudly('AlgumUsuários', 'AlgumUsuário', []);
        $this->assertPluralStudly('AlgumUsuário', 'AlgumUsuário', ['um']);
        $this->assertPluralStudly('AlgumUsuários', 'AlgumUsuário', ['um', 'dois']);
    }

    public function testPluralStudlySupportsCollections()
    {
        $this->assertPluralStudly('AlgumUsuários', 'AlgumUsuário', collect());
        $this->assertPluralStudly('AlgumUsuário', 'AlgumUsuário', collect(['um']));
        $this->assertPluralStudly('AlgumUsuários', 'AlgumUsuário', collect(['um', 'dois']));
    }

    private function assertPluralStudly($expected, $value, $count = 2)
    {
        $this->assertSame($expected, Str::pluralStudly($value, $count));
    }
}
