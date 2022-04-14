<?php

namespace App\Service\Mandados;

use Src\Edit;

class EditExemple extends Edit
{
    protected $deleteMissingObjectInObjectArrays = true;
    // protected $createMissingObjectInObjectArrays = true;
    protected $columnsCannotChange_defaults = [
        'id',
        'documento_id',
        'user_id',
        'tipo_documento_id',
        'sms_paciente_id',
        'documentacao_id',
        'endereco_id',
        'evolucao_administrativa_finalizacao_id',
        'evolucao_id',
        'evolucao_clinica_id',
        'evolucao_juridica_id',
        'evolucao_administrativa_id',
        'evolucao_clinica_status',
        'user_creator',
        'unidade',
        'sms_paciente',
        'smsPaciente'
    ];
    protected $relationshipsCannotChangeCameCase_defaults = [
        'evolucaoClinicaStatus',
        'userCreator',
        'unidade',
        'smsPaciente',
        'sistema',
        'anexo'
    ];

    protected $before = self::class;
    protected $after = self::class;
    protected $exception = self::class;

    private $user;
    private $tabelaAntiga = [];
    private $tabelaAntigaKey;
    private $tabelaAntigaKeysArray = [];
    private $ignorarHistoric = ['pivot','created_at','updated_at'];

    /**
     * Execuata tratamento antes do update.
     * Prepara para salvar a edição nas tabelas 'user_edit' e 'user_historic' usando o método a baido Edit::edit
     */
    public function antes($tabela)
    {
        $listarSessao = session()->get('listarSessao');
        $this->user = clone $listarSessao->dadosUsuario;

        $this->documentoId($tabela);

        $count = count($this->tabelaAntiga);

        $this->tabelaAntiga[$count] = clone $tabela;

        $this->tabelaAntigaKey = $count;
        $this->tabelaAntigaKeysArray[] = $count;

        return $this;
    }

    /**
     * Execuata tratamento depois do update.
     * Salva a edição nas tabelas 'user_edit' e 'user_historic'
     */
    public function depois($tabela)
    {
        $tabelaAntiga = $this->tabelaAntiga[$this->tabelaAntigaKey];

        foreach ($tabela->toArray() as $key => $value) {

            if ($key === 'id') continue;
            if (is_array($value)) continue;
            if (in_array($key, $this->ignorarHistoric)) continue;

            if ($tabelaAntiga[$key] != $value) {

                self::historicoEdit([
                    'tabela_alterado_id' => $tabela['id'],
                    'tabela_alterada' => $tabela->getTable(),
                    'coluna_alterada' => $key,
                    'conteudo_anterior' => json_encode($tabelaAntiga->$key, JSON_UNESCAPED_UNICODE),
                    'conteudo_alterado_para' => json_encode($value, JSON_UNESCAPED_UNICODE)
                ]);
            }
        }
    }

    /**
     * Onde são tratadas os cados q não podem ser tratados pelos EditaService, como array de objetos, ou não estruturados como o eloquent envia, ou outros motivos.
     * @param $tabela É o valor antigo
     */
    public function exception($tabela, $valoresNovos, $coluna, $create)
    {
        $camelCase = $this->snakeCaseParaCamelCase($coluna);

        switch ($camelCase) {
            case "key_name_of_exception":  break;

            default: return false; break;
        }
    }
}
