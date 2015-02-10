<?php
/*
 * Copyright (C) 2015 - Ednei Leite da Silva
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace application\controllers;

use \application\models\Usuarios as ModelUsuarios;
use \libs\Menu;

/**
 * Mantem usu�rios
 *
 * @author Ednei Leite da Silva
 */
class Usuarios extends \system\Controller {
	
	/**
	 * Objeto para obten��o de dados dos usu�rios.
	 *
	 * @var ModelUsuarios
	 */
	private $model;
	
	/**
	 * Verifica se usu�rios esta logado antes de executar opera��o
	 */
	public function __construct() {
		parent::__construct ();
		if (! Login::verifica_login ()) {
			$this->redir ( 'Login/index' );
		} else {
			$this->model = new ModelUsuarios ();
		}
	}
	
	/**
	 * Gera tela com formul�rio para inser��o de novo usu�rio
	 */
	public function cadastrar_usuario() {
		$permissao = 'Usuarios/cadastrar_usuario';
		
		if (Menu::possue_permissao ( $_SESSION ['perfil'], $permissao )) {
			$title = array (
					"title" => "Cadastro de usu�rio" 
			);
			
			$vars = array (
					'perfil' => $this->model->get_perfil ( $_SESSION ['perfil'] ),
					'link' => HTTP . '/Usuarios/novo_usuario',
					'title_botao' => "Cadastrar Usu�rio" 
			);
			
			$this->load_view ( "default/header", $title );
			$this->load_view ( "usuarios/usuario", $vars );
			$this->load_view ( "default/footer" );
		} else {
			$this->redir ( 'Main/index' );
		}
	}
	
	/**
	 * Realiza a inser��o de um novo usu�rio no sistema
	 */
	public function novo_usuario() {
		$permissao = 'Usuarios/cadastrar_usuario';
		
		if (Menu::possue_permissao ( $_SESSION ['perfil'], $permissao )) {
			if ($this->model->inserir_usuario ( $dados )) {
				$_SESSION ['msg_sucesso'] = "Usu�rio inserido com sucesso.";
			} else {
				$_SESSION ['msg_erro'] = "Erro ao inserir novo usu�rio. Verifique dados e tente novamente.";
			}
		} else {
			$this->redir ( 'Main/index' );
		}
	}
	private function get_dados_post_usuario() {
		$nome = $_POST ['inputNome'];
		$usuario = $_POST ['inputUsuario'];
		$senha = $_POST ['inputSenha'];
		$changeme = isset ( $_POST ['inputChangeme'] [0] ) ? TRUE : FALSE;
		$email = $_POST ['inputEMail'];
		$perfil = $_POST ['selectPerfil'];
		
		/* Verifica se todos os dados necess�rios foram informados */
		if (empty ( $nome ) || empty ( $usuario ) || empty ( $senha ) || empty ( $email ) || empty ( $perfil )) {
			
			$_SESSION ['msg_erro'] = "Dados informados inv�lidos. Verifique dados inseridos e tente novamente.";
		} else {
			$datetime = NULL;
			
			// caso o usu�rio tenha selecionado "Senha tempor�ria"
			// seta data de troca para "HOJE"
			if ($changeme) {
				$datetime = new \DateTime ();
			} else {
				$datetime = new \DateTime ();
				$datetime->add ( new \DateInterval ( 'P30D' ) );
			}
			
			$dados = array (
					'usuario' => $usuario,
					'senha' => sha1 ( md5 ( $senha ) ),
					'nome' => $nome,
					'email' => $email,
					'perfil' => $perfil,
					'dt_troca' => $datetime->format ( 'Y-m-d' ) 
			);
		}
		
		$this->redir ( 'Usuarios/cadastrar_usuario' );
	}
	
	/**
	 * Verifica se o usu�rio existe
	 */
	public function valida_usuario() {
		$permissao_1 = 'Usuarios/cadastrar_usuario';
		$permissao_2 = 'Usuarios/alterar_usuario';
		$perfil = $_SESSION ['perfil'];
		
		if (Menu::possue_permissao ( $perfil, $permissao_1 ) || Menu::possue_permissao ( $perfil, $permissao_2 )) {
			$user = $_POST ['user'];
			$id = $_POST ['id'];
			
			echo json_encode ( $this->model->get_usuario ( $user, $id ) );
		}
	}
	
	/**
	 * Verifica se existe email para algum usu�rio
	 */
	public function valida_email() {
		$permissao_1 = 'Usuarios/cadastrar_usuario';
		$permissao_2 = 'Usuarios/alterar_usuario';
		$perfil = $_SESSION ['perfil'];
		
		if (Menu::possue_permissao ( $perfil, $permissao_1 ) || Menu::possue_permissao ( $perfil, $permissao_2 )) {
			$email = $_POST ['email'];
			$id = $_POST ['id'];
			
			echo json_encode ( $this->model->get_email ( $email, $id ) );
		}
	}
	
	/**
	 * Busca usu�rio para realizar altera��o
	 */
	public function alterar_usuario() {
		$permissao = 'Usuarios/alterar_usuario';
		
		if (Menu::possue_permissao ( $_SESSION ['perfil'], $permissao )) {
			$title = array (
					"title" => "Alterar usu�rio" 
			);
			
			$usuarios ['usuarios'] = $this->model->get_id_usuarios ( $_SESSION ['perfil'] );
			
			$vars = array (
					'perfil' => $this->model->get_perfil ( $_SESSION ['perfil'] ),
					'link' => HTTP . '/Usuarios/atualiza_usuario',
					'title_botao' => "Alterar Usu�rio" 
			);
			
			$this->load_view ( "default/header", $title );
			$this->load_view ( "usuarios/relacao_usuarios", $usuarios );
			$this->load_view ( "usuarios/usuario", $vars );
			$this->load_view ( "default/footer" );
		} else {
			$this->redir ( 'Main/index' );
		}
	}
	
	/**
	 * Busca dados do usuario selecionado para altera��o
	 */
	public function get_dados_usuarios() {
		$permissao = 'Usuarios/alterar_usuario';
		$perfil = $_SESSION ['perfil'];
		
		if (Menu::possue_permissao ( $perfil, $permissao )) {
			$id = $_POST ['id'];
			
			echo json_encode ( $this->model->get_dados_usuarios ( $id ) );
		}
	}
	public function atualiza_usuario() {
	}
}