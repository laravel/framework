<?php

class SupportPluralizerTest extends PHPUnit_Framework_TestCase {

	public function testEnglish()
	{
		$this->assertEquals('children', str_plural('child'));
		$this->assertEquals('tests', str_plural('test'));
		$this->assertEquals('deer', str_plural('deer'));
		$this->assertEquals('dishes', str_plural('dish'));
		$this->assertEquals('judges', str_plural('judge'));
		$this->assertEquals('laps', str_plural('lap'));
		$this->assertEquals('clocks', str_plural('clock'));
		$this->assertEquals('cuffs', str_plural('cuff'));
		$this->assertEquals('deaths', str_plural('death'));
		$this->assertEquals('heroes', str_plural('hero'));
		$this->assertEquals('potatoes', str_plural('potato'));
		$this->assertEquals('photos', str_plural('photo'));
		$this->assertEquals('zeros', str_plural('zero'));
		$this->assertEquals('ladies', str_plural('lady'));
		$this->assertEquals('monkeys', str_plural('monkey'));
		$this->assertEquals('baths', str_plural('bath'));
		$this->assertEquals('calves', str_plural('calf'));
		$this->assertEquals('leaves', str_plural('leaf'));
		$this->assertEquals('proofs', str_plural('proof'));
		$this->assertEquals('women', str_plural('woman'));
		$this->assertEquals('teeth', str_plural('tooth'));
		$this->assertEquals('salmon', str_plural('salmon'));
		$this->assertEquals('fish', str_plural('fish'));
		$this->assertEquals('matrices', str_plural('matrix'));
		$this->assertEquals('crises', str_plural('crisis'));
		$this->assertEquals('millennia', str_plural('millennium'));
		$this->assertEquals('ninjas', str_plural('ninja'));
 	

		$this->assertEquals('child', str_singular('children'));
		$this->assertEquals('test', str_singular('tests'));
		$this->assertEquals('deer', str_singular('deer'));
		$this->assertEquals('dish', str_singular('dishes'));
		$this->assertEquals('judge', str_singular('judges'));
		$this->assertEquals('lap', str_singular('laps'));
		$this->assertEquals('clock', str_singular('clocks'));
		$this->assertEquals('cuff', str_singular('cuffs'));
		$this->assertEquals('death', str_singular('deaths'));
		$this->assertEquals('hero', str_singular('heroes'));
		$this->assertEquals('potato', str_singular('potatoes'));
		$this->assertEquals('photo', str_singular('photos'));
		$this->assertEquals('zero', str_singular('zeros'));
		$this->assertEquals('lady', str_singular('ladies'));
		$this->assertEquals('monkey', str_singular('monkeys'));
		$this->assertEquals('bath', str_singular('baths'));
		$this->assertEquals('calf', str_singular('calves'));
		$this->assertEquals('leaf', str_singular('leaves'));
		$this->assertEquals('proof', str_singular('proofs'));
		$this->assertEquals('woman', str_singular('women'));
		$this->assertEquals('tooth', str_singular('teeth'));
		$this->assertEquals('salmon', str_singular('salmon'));
		$this->assertEquals('fish', str_singular('fish'));
		$this->assertEquals('matrix', str_singular('matrices'));
		$this->assertEquals('crisis', str_singular('crises'));
		$this->assertEquals('millennium', str_singular('millennia'));
		$this->assertEquals('ninja', str_singular('ninjas'));
	}





	public function testFrench()
	{
		$this->assertEquals('généraux', str_plural('général', 2, 'fr'));
		$this->assertEquals('amicales', str_plural('amicale', 2, 'fr'));
		$this->assertEquals('détails', str_plural('détail', 2, 'fr'));
		$this->assertEquals('travaux', str_plural('travail', 2, 'fr'));
		$this->assertEquals('filous', str_plural('filou', 2, 'fr'));
		$this->assertEquals('genoux', str_plural('genou', 2, 'fr'));
		$this->assertEquals('bureaux', str_plural('bureau', 2, 'fr'));
		$this->assertEquals('oiseaux', str_plural('oiseau', 2, 'fr'));
		$this->assertEquals('jeux', str_plural('jeu', 2, 'fr'));
		$this->assertEquals('chevaux', str_plural('cheval', 2, 'fr'));
		$this->assertEquals('sarraus', str_plural('sarrau', 2, 'fr'));
		$this->assertEquals('bleus', str_plural('bleu', 2, 'fr'));
		$this->assertEquals('cieux', str_plural('ciel', 2, 'fr'));
		$this->assertEquals('ails', str_plural('ail', 2, 'fr'));
		$this->assertEquals('corps', str_plural('corps', 2, 'fr'));
		$this->assertEquals('yeux', str_plural('oeil', 2, 'fr'));
		$this->assertEquals('mesdames', str_plural('madame', 2, 'fr'));
		$this->assertEquals('messieurs', str_plural('monsieur', 2, 'fr'));
		$this->assertEquals('ils', str_plural('il', 2, 'fr'));
		$this->assertEquals('des', str_plural('un', 2, 'fr'));
		$this->assertEquals('les', str_plural('le', 2, 'fr'));
		$this->assertEquals('les', str_plural('la', 2, 'fr'));
		$this->assertEquals('familles', str_plural('famille', 2, 'fr'));
		$this->assertEquals('nez', str_plural('nez', 2, 'fr'));


		$this->assertEquals('détail', str_singular('détails', 'fr'));
		$this->assertEquals('travail', str_singular('travaux', 'fr'));
		$this->assertEquals('filou', str_singular('filous', 'fr'));
		$this->assertEquals('genou', str_singular('genoux', 'fr'));
		$this->assertEquals('bureau', str_singular('bureaux', 'fr'));
		$this->assertEquals('oiseau', str_singular('oiseaux', 'fr'));
		$this->assertEquals('jeu', str_singular('jeux', 'fr'));
		$this->assertEquals('sarrau', str_singular('sarraus', 'fr'));
		$this->assertEquals('bleu', str_singular('bleus', 'fr'));
		$this->assertEquals('ciel', str_singular('cieux', 'fr'));
		$this->assertEquals('ail', str_singular('ails', 'fr'));
		$this->assertEquals('corps', str_singular('corps', 'fr'));
		$this->assertEquals('oeil', str_singular('yeux', 'fr'));
		$this->assertEquals('madame', str_singular('mesdames', 'fr'));
		$this->assertEquals('monsieur', str_singular('messieurs', 'fr'));
		$this->assertEquals('il', str_singular('ils', 'fr'));
		$this->assertEquals('un', str_singular('des', 'fr'));
		$this->assertEquals('nez', str_singular('nez', 'fr'));
		$this->assertEquals('famille', str_singular('familles', 'fr'));
	}
}
