<?php
/**
* 
* Frete com Correios para Magento
* 
* @category     elOOm
* @package      Modulo Frete com Correios
* @copyright    Copyright (c) 2021 elOOm (https://eloom.tech)
* @version      1.0.1
* @license      https://opensource.org/licenses/OSL-3.0
* @license      https://opensource.org/licenses/AFL-3.0
*
*/
declare(strict_types=1);

namespace Eloom\CorreiosFrete\Lib\CalcPrecoPrazo;

class CalcPrecoPrazoData {

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

	public $sDtCalculo = null;

	public function __construct($nCdEmpresa, $sDsSenha, $nCdServico, $sCepOrigem, $sCepDestino, $nVlPeso, $nCdFormato, $nVlComprimento, $nVlAltura, $nVlLargura, $nVlDiametro, $sCdMaoPropria, $nVlValorDeclarado, $sCdAvisoRecebimento, $sDtCalculo) {
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
		$this->sDtCalculo = $sDtCalculo;
	}

}
