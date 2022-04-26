<?php

namespace Meunik\Edit;

use Meunik\Edit\Edit;

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
        //
    }

    /**
     * Execuata tratamento depois do update.
     * Salva a edição nas tabelas 'user_edit' e 'user_historic'
     */
    public function depois($tabela)
    {
        //
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
