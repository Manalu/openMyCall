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
namespace application\models;

/**
 * Manipula dados referentes a login dos usu�rios
 *
 * @author Ednei Leite da Silva
 */
class Login extends \system\Model {
	
	/**
	 * Pesquisa dados do usu�rio
	 * 
	 * @param string $usuario        	
	 * @param string $senha        	
	 * @return Array Retorna array com <b>nome</b>, <b>usuario</b>, <b>email</b>, <b>perfil</b>.
	 */
	public function get_dados_login($usuario, $senha) {
		$sql = "SELECT usuario.nome, usuario.usuario, usuario.email, perfil.perfil FROM usuario
                INNER JOIN perfil ON usuario.perfil = perfil.id
                WHERE usuario.senha = sha1(md5(:senha)) AND usuario.usuario = :usuario";
		
		$array = array (
				'senha' => $senha,
				'usuario' => $usuario 
		);
		
		return $this->select ( $sql, $array, FALSE );
	}
}
