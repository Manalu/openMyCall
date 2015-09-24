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

/**
 * Manipula horarios de trabalho e dias de trabalhados
 *
 * @author Ednei Leite da Silva
 */
class Horarios_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Busca todos os feriados cadastrado no sistema
     *
     * @param boolean $mostrar Sinaliza se determinada data será exibida
     * @return array
     */
    public function getFeriados($mostrar) {
        $this->db->select("dia, '', nome");
        $query = $this->db->from('phpmycall.feriado')->get();

        $result = $query->result_array();

        $return = array();

        foreach ($result as $values) {
            $return[$values['dia']] = array($mostrar, '', $values['nome']);
        }

        return $return;
    }

    /**
     * Adiciona um ou mais feriados
     *
     * @param string $data Data do feriado
     * @param string $nome Nome do feriado
     * @param boolean $replicar Replicar o feriado nos próximos 15 anos se valor igual a TRUE
     * @return Array Retorna dados para gravação de log
     */
    public function addFeriados($data, $nome, $replicar) {
        $return = array();

        /*
         * Se valor de replicar igual a true replica a data nos 15 anos seguintes
         */
        if ($replicar) {
            for ($inicio = 0; $inicio < 15; $inicio ++) {
                $obj_data = new DateTime($data);
                $obj_data = $obj_data->add(new DateInterval('P' . $inicio . 'Y'));

                unset($array);

                $array = array(
                    'dia' => $obj_data->format('Y-m-d'),
                    'nome' => $nome
                );

                $return[$inicio]['dados'] = $array;
                $return[$inicio]['result'] = $this->db->insert('phpmycall.feriado', $array);
            }
        } else {
            /*
             * Se replicar igual a false grava feriado
             * apenas para a data selecionada
             */
            $obj_data = new DateTime($data);

            $array = array(
                'dia' => $obj_data->format('Y-m-d'),
                'nome' => $nome
            );

            $return[0]['dados'] = $array;
            $return[0]['result'] = $this->db->insert('phpmycall.feriado', $array);
        }

        return $return;
    }

    /**
     * Retorna o nome do feriado
     *
     * @param string $dia Data do feriado
     * @return array Retorna array com o nome do feriado
     */
    public function getFeriadoByDia($dia) {
        $query = $this->db->select('nome')->from('phpmycall.feriado')->where('dia', $dia)->get();

        return $query->row_array();
    }

    /**
     * Atualiza nome do feriado
     *
     * @param string $data Data do feriado.
     * @param string $nome Nome do feriado.
     * @return boolean Retorna <b>TRUE</b> em caso de sucesso.
     */
    public function updateFeriados($data, $nome) {
        $this->db->where('dia', $data);
        return $this->db->update('phpmycall.feriado', array('nome' => $nome));
    }

    /**
     * Exclui um feriado
     *
     * @param string $data Dia do feriado.
     * @return boolean <b>TRUE</b> sucesso, <b>FALSE</b> falha.
     */
    public function deleteFeriados($data) {
        $this->db->where('dia', $data);
        return $this->db->delete('phpmycall.feriado');
    }

    /**
     * Busca os dias e horarios de expediente.
     *
     * @return Array Retorna array com os dia da semana e horários de entrada e saída do 1º e 2º periodo.
     */
    public function getExpediente() {
        $sql = "SELECT id,
                    dia_semana,
                    TO_CHAR(entrada_manha, 'HH24:MI') AS entrada_manha,
                    TO_CHAR(saida_manha, 'HH24:MI') AS saida_manha,
                    TO_CHAR(entrada_tarde, 'HH24:MI') AS entrada_tarde,
                    TO_CHAR(saida_tarde, 'HH24:MI') AS saida_tarde
                FROM phpmycall.expediente ORDER BY id;";

        $result = $this->db->query($sql);

        foreach ($result->result_array() as $values) {
            $return ['dia_semana'] [$values ['id']] = $values ['dia_semana'];
            $return ['entrada_manha'] [$values ['id']] = $values ['entrada_manha'];
            $return ['saida_manha'] [$values ['id']] = $values ['saida_manha'];
            $return ['entrada_tarde'] [$values ['id']] = $values ['entrada_tarde'];
            $return ['saida_tarde'] [$values ['id']] = $values ['saida_tarde'];
        }

        return $return;
    }

    /**
     * Altera horário de entrada ou saida de determinado dia.
     *
     * @param int $id ID do dia da semana
     * @param string $value Novo horário
     * @param string $coluna Qual periodo será alterado
     * @return boolean <b>TRUE</b> sucesso, <b>FALSE</b> falha.
     */
    public function setExpediente($id, $value, $coluna) {
        $dados = array(
            $coluna => empty($value) ? NULL : $value
        );

        $this->db->where('id', $id);
        return $this->db->update('phpmycall.expediente', $dados);
    }

}