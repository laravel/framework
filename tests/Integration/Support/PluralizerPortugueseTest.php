<?php

namespace Illuminate\Tests\Integration\Support;

use Illuminate\Support\Pluralizer;
use Illuminate\Support\StrGrammar;
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
        $this->assertSame('herói', StrGrammar::singular('heróis'));
        $this->assertSame('irmão', StrGrammar::singular('irmãos'));
        $this->assertSame('chafariz', StrGrammar::singular('chafarizes'));
        $this->assertSame('colher', StrGrammar::singular('colheres'));
        $this->assertSame('modelo', StrGrammar::singular('modelos'));
        $this->assertSame('venda', StrGrammar::singular('vendas'));
        $this->assertSame('usuário', StrGrammar::singular('usuários'));
        $this->assertSame('comissão', StrGrammar::singular('comissões'));
    }

    public function testIrregulars()
    {
        $this->assertSame('males', StrGrammar::plural('mal'));
        $this->assertSame('lápis', StrGrammar::singular('lápis'));
    }

    public function testBasicPlural()
    {
        $this->assertSame('fênix', StrGrammar::plural('fênix'));
        $this->assertSame('palavras', StrGrammar::plural('palavra'));
        $this->assertSame('modelos', StrGrammar::plural('modelo'));
        $this->assertSame('vendas', StrGrammar::plural('venda'));
        $this->assertSame('usuários', StrGrammar::plural('usuário'));
        $this->assertSame('comissões', StrGrammar::plural('comissão'));
    }

    public function testCaseSensitiveSingularUsage()
    {
        $this->assertSame('Criança', StrGrammar::singular('Crianças'));
        $this->assertSame('CIDADÃO', StrGrammar::singular('CIDADÃOS'));
    }

    public function testCaseSensitiveSingularPlural()
    {
        $this->assertSame('Crianças', StrGrammar::plural('Criança'));
        $this->assertSame('CIDADÃOS', StrGrammar::plural('CIDADÃO'));
        $this->assertSame('Testes', StrGrammar::plural('Teste'));
    }

    public function testPluralAppliedForStringEndingWithNumericCharacter()
    {
        $this->assertSame('Usuário1s', StrGrammar::plural('Usuário1'));
        $this->assertSame('Usuário2s', StrGrammar::plural('Usuário2'));
        $this->assertSame('Usuário3s', StrGrammar::plural('Usuário3'));
    }

    public function testPluralSupportsArrays()
    {
        $this->assertSame('usuários', StrGrammar::plural('usuário', []));
        $this->assertSame('usuário', StrGrammar::plural('usuário', ['um']));
        $this->assertSame('usuários', StrGrammar::plural('usuário', ['um', 'dois']));
    }

    public function testPluralSupportsCollections()
    {
        $this->assertSame('usuários', StrGrammar::plural('usuário', collect()));
        $this->assertSame('usuário', StrGrammar::plural('usuário', collect(['um'])));
        $this->assertSame('usuários', StrGrammar::plural('usuário', collect(['um', 'dois'])));
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
        $this->assertSame($expected, StrGrammar::pluralStudly($value, $count));
    }
}
