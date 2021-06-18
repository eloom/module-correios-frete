<?php
/**
* 
* Frete com Correios para Magento 2
* 
* @category     Ã©lOOm
* @package      Modulo Frete com Correios
* @copyright    Copyright (c) 2021 Ã©lOOm (https://eloom.tech)
* @version      1.0.0
* @license      https://opensource.org/licenses/OSL-3.0
* @license      https://opensource.org/licenses/AFL-3.0
*
*/
declare(strict_types=1);

namespace Eloom\CorreiosFrete\Lib\CalcPrecoPrazo;

class CalcPrecoPrazo {

	public $nCdEmpresa = null;

	public $sDsSenha = null;

	public $nCdServico = null;

	public $sCepOrigem = null;

	public $sCepDestino = null;

	public $nVlPeso = null;

	public $nCdFormato = null;

	public $nVlComprimento = null;

	public $nVlAltura = null;

	public $nVlLargura = null;

	public $nVlDiametro = null;

	public $sCdMaoPropria = null;

	public $nVlValorDeclarado = null;

	public $sCdAvisoRecebimento = null;

	public function __construct($nCdEmpresa = null, $sDsSenha = null, $nCdServico = null, $sCepOrigem = null, $sCepDestino = null, $nVlPeso = null, $nCdFormato = null, $nVlComprimento = null, $nVlAltura = null, $nVlLargura = null, $nVlDiametro = null, $sCdMaoPropria = null, $nVlValorDeclarado = null, $sCdAvisoRecebimento = null) {
		$this->nCdEmpresa = $nCdEmpresa;
		$this->sDsSenha = $sDsSenha;
		$this->nCdServico = $nCdServico;
		$this->sCepOrigem = $sCepOrigem;
		$this->sCepDestino = $sCepDestino;
		$this->nVlPeso = $nVlPeso;
		$this->nCdFormato = $nCdFormato;
		$this->nVlComprimento = $nVlComprimento;
		$this->nVlAltura = $nVlAltura;
		$this->nVlLargura = $nVlLargura;
		$this->nVlDiametro = $nVlDiametro;
		$this->sCdMaoPropria = $sCdMaoPropria;
		$this->nVlValorDeclarado = $nVlValorDeclarado;
		$this->sCdAvisoRecebimento = $sCdAvisoRecebimento;
	}

}
