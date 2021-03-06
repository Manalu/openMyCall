<?php

/*
 * Copyright (C) 2015 - 2016, Ednei Leite da Silva
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

/**
 * Manipulas os projetos e tipos de problemas
 *
 * @author Ednei Leite da Silva
 */
class Projetos_problemas extends Admin_Controller {

    /**
     * Construtor
     */
    public function __construct() {
        parent::__construct('projetos_problemas');
        $this->load->model('projetos_problemas_model', 'model');
    }

    /**
     * Gera tela para manutenção de projetos e problemas
     */
    public function index() {
        $permissao = "projetos_problemas/index";
        $perfil = $_SESSION['perfil'];

        if (Menu::possue_permissao($perfil, $permissao)) {
            $this->load->helper('form');
            $nivel = $_SESSION['nivel'];

            $vars = array(
                'usuarios' => $this->model->relacao_usuarios($nivel)
            );

            $this->load_view(array('projetos_problemas/index', 'projetos_problemas/form'), $vars);
        } else {
            redirect('main');
        }
    }

    public function lista_projeto_problemas() {
        $permissao = 'projetos_problemas/index';
        $perfil = $_SESSION['perfil'];

        if (Menu::possue_permissao($perfil, $permissao)) {
            $limit = filter_input(INPUT_POST, 'limit', FILTER_SANITIZE_NUMBER_INT);
            $offset = filter_input(INPUT_POST, 'offset', FILTER_SANITIZE_NUMBER_INT);
            $sort = filter_input(INPUT_POST, 'sort', FILTER_SANITIZE_STRING);
            $order = filter_input(INPUT_POST, 'order', FILTER_SANITIZE_STRING);
            $search = filter_input(INPUT_POST, 'search', FILTER_SANITIZE_STRING);

            $columns = array(
                'id_projeto' => 'projeto.id',
                'projeto' => 'projeto.nome',
                'problema' => 'tipo_problema.nome'
            );

            $order_by = "{$columns[$sort]} {$order}";

            $return = $this->model->lista_projeto_problemas($search, $order_by, $limit, $offset);

            $this->response($return);
        }
    }

    /**
     * Busca os tipos de projetos
     */
    public function get_projetos() {
        $permissao = "projetos_problemas/index";
        $perfil = $_SESSION['perfil'];

        if (Menu::possue_permissao($perfil, $permissao)) {
            $nome = filter_input(INPUT_POST, 'term', FILTER_SANITIZE_STRING, FILTER_FLAG_EMPTY_STRING_NULL);

            $this->response($this->model->get_projetos($nome));
        }
    }

    /**
     * Busca os tipos de problemas existentes
     */
    public function get_problemas() {
        $permissao = "projetos_problemas/index";
        $perfil = $_SESSION ['perfil'];

        if (Menu::possue_permissao($perfil, $permissao)) {
            $nome = filter_input(INPUT_POST, 'term', FILTER_SANITIZE_STRING, FILTER_FLAG_EMPTY_STRING_NULL);

            $this->response($this->model->get_problemas($nome));
        }
    }

    /**
     * Busca ID de um projeto
     */
    public function get_dados_projeto() {
        $permissao = "projetos_problemas/index";
        $perfil = $_SESSION['perfil'];

        if (Menu::possue_permissao($perfil, $permissao)) {
            $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING, FILTER_FLAG_EMPTY_STRING_NULL);

            $id = $this->model->get_id_projeto($nome);

            $vars = $this->model->get_descricao_projeto($id);
            $vars['participantes'] = $this->model->get_relacao_participantes($id);

            $this->response($vars);
        }
    }

    /**
     * Insere um novo projeto com os respectivos participantes
     */
    public function cadastrar() {
        $permissao = 'projetos_problemas/index';
        $perfil = $_SESSION ['perfil'];

        if (Menu::possue_permissao($perfil, $permissao)) {
            $this->load->library('utils');

            $valida_hora = array('options' => array('Utils', 'valida_formato_hora'));

            $participantes = filter_input(INPUT_POST, 'participantes', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $projeto = filter_input(INPUT_POST, 'input_nome_projeto', FILTER_SANITIZE_STRING, FILTER_FLAG_EMPTY_STRING_NULL);
            $problema = filter_input(INPUT_POST, 'input_nome_problema', FILTER_SANITIZE_STRING, FILTER_FLAG_EMPTY_STRING_NULL);
            $resposta = filter_input(INPUT_POST, 'input_resposta', FILTER_CALLBACK, $valida_hora);
            $solucao = filter_input(INPUT_POST, 'input_solucao', FILTER_CALLBACK, $valida_hora);
            $descricao_projeto = filter_input(INPUT_POST, 'text_projeto', FILTER_SANITIZE_STRING, FILTER_FLAG_EMPTY_STRING_NULL);
            $descricao = filter_input(INPUT_POST, 'text_descricao', FILTER_SANITIZE_STRING, FILTER_FLAG_EMPTY_STRING_NULL);

            if (!$this->model->existe_projeto_problema($projeto, $problema)) {
                $id_projeto = $this->model->get_id_projeto($projeto);
                $id = $id_projeto;

                if (empty($id_projeto)) {
                    $id_projeto = $this->model->insert_projeto($projeto, $descricao_projeto);

                    if (empty($id_projeto)) {
                        $dados = array('status' => false, 'msg' => $this->translate['response_error_create_project']);
                    } else if (!empty($participantes)) {
                        $this->model->adiciona_partcipantes_projeto($participantes, $id_projeto);
                    }
                }

                if (!empty($id_projeto)) {
                    $id_problema = $this->model->get_id_problema($problema);

                    if (empty($id_problema)) {
                        $id_problema = $this->model->insert_tipo_problema($problema);

                        if (empty($id_problema)) {
                            $dados = array('status' => false, 'msg' => $this->translate['response_error_create_problem']);
                        }
                    }

                    if (!empty($id_problema)) {
                        $this->model->cria_projeto_problemas($id_projeto, $id_problema, $resposta, $solucao, $descricao);
                        $dados = array('status' => true, 'msg' => $this->translate['response_success_create_project']);
                    }
                }

                $dados_log = array(
                    'dados' => array(
                        'operacao' => empty($id) ? $this->translate['msg_log_create_project'] : $this->translate['msg_log_new_type_problem'],
                        'id_projeto' => $id_projeto,
                        'nome_projeto' => $projeto,
                        'descricao_projeto' => $descricao_projeto,
                        'id_tipo_problema' => $id_problema,
                        'nome_tipo_problema' => $problema,
                        'tempo_resposta' => $resposta,
                        'tempo_solucao' => $solucao,
                        'novos_usuarios' => (empty($participantes) ? array() : implode(',', $participantes))
                    )
                );
            } else {
                $dados = array('status' => false, 'msg' => $this->translate['response_error_exist_project_problem']);
            }

            $dados_log ['msg'] = $dados['msg'];
            $dados_log ['aplicacao'] = $permissao;

            Logs::gravar($dados_log, $_SESSION ['id']);

            $this->response($dados);
        }
    }

    /**
     * Busca informações sobre o projeto.
     */
    public function get_dados_projeto_problemas() {
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $permissao = "projetos_problemas/index";
        $perfil = $_SESSION['perfil'];

        if (Menu::possue_permissao($perfil, $permissao)) {
            $this->response($this->model->get_dados_projeto_problema($id));
        }
    }

    /**
     * Realiza a operação de atualização do projeto (alterar)
     */
    public function alterar() {
        $permissao = 'projetos_problemas/index';
        $perfil = $_SESSION ['perfil'];

        if (Menu::possue_permissao($perfil, $permissao)) {
            $this->load->library('utils');

            $valida_hora = array('options' => array('Utils', 'valida_formato_hora'));

            $id_projeto = filter_input(INPUT_POST, 'input_projeto', FILTER_SANITIZE_NUMBER_INT);
            $id_problema = filter_input(INPUT_POST, 'input_problema', FILTER_SANITIZE_NUMBER_INT);
            $id_projeto_problema = filter_input(INPUT_POST, 'input_projeto_problema', FILTER_SANITIZE_NUMBER_INT);
            $participantes = filter_input(INPUT_POST, 'participantes', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $projeto = filter_input(INPUT_POST, 'input_nome_projeto', FILTER_SANITIZE_STRING, FILTER_FLAG_EMPTY_STRING_NULL);
            $problema = filter_input(INPUT_POST, 'input_nome_problema', FILTER_SANITIZE_STRING, FILTER_FLAG_EMPTY_STRING_NULL);
            $resposta = filter_input(INPUT_POST, 'input_resposta', FILTER_CALLBACK, $valida_hora);
            $solucao = filter_input(INPUT_POST, 'input_solucao', FILTER_CALLBACK, $valida_hora);
            $descricao_projeto = filter_input(INPUT_POST, 'text_projeto', FILTER_SANITIZE_STRING, FILTER_FLAG_EMPTY_STRING_NULL);
            $descricao = filter_input(INPUT_POST, 'text_descricao', FILTER_SANITIZE_STRING, FILTER_FLAG_EMPTY_STRING_NULL);

            $update_projeto = array(
                'nome' => $projeto,
                'descricao' => $descricao_projeto
            );

            if ($this->model->altera_projeto($update_projeto, $id_projeto)) {
                $participantes_old = $this->model->get_relacao_participantes($id_projeto);
                $insert = empty($participantes) ? array() : $participantes;
                $delete = array();

                foreach ($participantes_old as $value) {
                    if (!in_array($value, $insert)) {
                        $this->model->delete_participantes_projeto($value, $id_projeto);
                        $delete [] = $value;
                    }

                    $key = array_search($value, $insert);
                    if ($key !== false) {
                        unset($insert [$key]);
                    }
                }

                $this->model->adiciona_partcipantes_projeto($insert, $id_projeto);

                $delete = implode(',', $delete);
                $insert = implode(',', $insert);

                $id_problema = $this->model->get_id_problema($problema);

                if (empty($id_problema)) {
                    $id_problema = $this->model->insert_tipo_problema($problema);

                    if (empty($id_problema)) {
                        $dados = array('status' => false, 'msg' => $this->translate['response_error_create_problem']);
                    }
                }

                /*
                 * Verifica se foi inserido ou existe tipo do problema
                 */
                if (!empty($id_problema)) {
                    $this->model->atualiza_projeto_problemas($id_projeto_problema, $id_projeto, $id_problema, $resposta, $solucao, $descricao);
                    $dados = array('status' => true, 'msg' => $this->translate['response_success_update_project_problem']);
                }

                $dados_log = array(
                    'dados' => array(
                        'id_projeto' => $id_projeto,
                        'nome_projeto' => $projeto,
                        'descricao_projeto' => $descricao_projeto,
                        'id_tipo_problema' => $id_problema,
                        'nome_tipo_problema' => $problema,
                        'id_projeto_tipo_problema' => $id_projeto_problema,
                        'tempo_resposta' => $resposta,
                        'tempo_solucao' => $solucao,
                        'projeto_tipo_problema' => $descricao,
                        'novos_usuarios' => $insert,
                        'excluir_usuarios' => $delete
                    ),
                    'msg' => $dados['msg'],
                    'aplicacao' => $permissao
                );

                Logs::gravar($dados, $_SESSION ['id']);

                $this->response($dados);
            }
        }
    }

    /**
     * Realiza a exclusão do projeto tipo de problema selecionado
     */
    public function excluir() {
        $permissao = "projetos_problemas/index";

        if (Menu::possue_permissao($_SESSION ['perfil'], $permissao)) {
            $id_projeto = filter_input(INPUT_POST, 'projeto', FILTER_SANITIZE_NUMBER_INT);
            $id_projeto_problema = filter_input(INPUT_POST, 'projeto_problema', FILTER_SANITIZE_NUMBER_INT);

            if ($this->model->excluir_projeto_problemas($id_projeto, $id_projeto_problema)) {
                $dados = array('status' => true, 'msg' => $this->translate['response_success_remove_project_problem']);
            } else {
                $dados = array('status' => false, 'msg' => $this->translate['response_error_remove_project_problem']);
            }

            $log = array(
                'dados' => array(
                    'id_projeto' => $id_projeto,
                    'id_projeto_problema' => $id_projeto_problema
                ),
                'aplicacao' => $permissao,
                'msg' => $dados['msg']
            );

            Logs::gravar($log, $_SESSION ['id']);

            $this->response($dados);
        }
    }

}
