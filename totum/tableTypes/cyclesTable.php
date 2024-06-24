<?php
/**
 * Created by PhpStorm.
 * User: tatiana
 * Date: 20.03.17
 * Time: 13:26
 */

namespace totum\tableTypes;

use totum\common\calculates\CalculateAction;
use totum\common\errorException;
use totum\common\Cycle;
use totum\common\FormatParamsForSelectFromTable;
use totum\common\sql\SqlException;
use totum\common\Totum;

class cyclesTable extends RealTables
{
    protected function __construct(Totum $Totum, $tableRow, $extraData = null, $light = false, $hash = null)
    {
        parent::__construct($Totum, $tableRow, $extraData, $light, $hash);

        if ($this->tableRow['deleting'] === 'hide') {
            $this->tableRow['deleting'] = 'delete';
        }
    }

    public function isCalcsTableFromThisCyclesTable(mixed $table): bool
    {
        $tableRow = $this->getTotum()->getTableRow($table);
        if ($tableRow['type'] !== 'calcs') {
            return false;
        }
        if ((int)$tableRow['tree_node_id'] === $this->getTableRow()['id']) {
            return true;
        }
        return false;
    }

    public function createTable(int $duplicatedId)
    {
        parent::createTable($duplicatedId);
        if (!$duplicatedId) {
            $tablesFields = $this->Totum->getTable('tables_fields');
            $tablesFields->reCalculateFromOvers(
                ['add' => [
                    0 => [
                        'table_id' => $this->tableRow['id']
                        , 'name' => 'creator_id'
                        , 'category' => 'column'
                        , 'ord' => '10'
                        , 'title' => $this->translate('User access')
                        , 'data_src' => [
                            'type' => ['Val' => 'select', 'isOn' => true]
                            , 'width' => ['Val' => 100, 'isOn' => true]
                            , 'filterable' => ['Val' => true, 'isOn' => true]
                            , 'showInWeb' => ['Val' => true, 'isOn' => true]
                            , 'editable' => ['Val' => false, 'isOn' => true]
                            , 'linkFieldName' => ['Val' => 'creator_id', 'isOn' => true]
                            , 'code' => ['Val' => "=: listCreate(item: \$user)\nuser: nowUser()", 'isOn' => true]
                            , 'codeOnlyInAdd' => ['Val' => true, 'isOn' => true]
                            , 'webRoles' => ['Val' => ['1'], 'isOn' => true]
                            , 'codeSelect' => ['Val' => "=:SelectListAssoc(table: 'users';field: 'fio';)", 'isOn' => true]
                            , 'multiple' => ['Val' => true, 'isOn' => true]
                        ]
                    ],
                    2 => [
                        'table_id' => $this->tableRow['id']
                        , 'name' => 'button_to_cycle'
                        , 'category' => 'column'
                        , 'ord' => '30'
                        , 'title' => $this->translate('Button to the cycle')
                        , 'data_src' => [
                            'type' => ['Val' => 'button', 'isOn' => true]
                            , 'width' => ['Val' => 100, 'isOn' => true]
                            , 'showInWeb' => ['Val' => true, 'isOn' => true]
                            , 'buttonText' => ['Val' => $this->translate('Open'), 'isOn' => true]
                            , 'codeAction' => ['Val' => "= : linkToTable(table: \$table; cycle: #id; target: 'self' )\n"
                                . 'table: select(table: \'tables\';  field: \'id\' ; where: \'type\'="calcs"; where: \'tree_node_id\'=$nt; order: \'sort\' )' . "\n"
                                . 'nt: nowTableId()', 'isOn' => true]
                        ]
                    ]
                ]
                ]
            );
        }
    }

    public function deleteTable()
    {
        $ids = array_keys($this->model->getAllIndexedById([], 'id'));
        foreach ($ids as $id) {
            try {
                $this->Totum->deleteCycle($id, $this->tableRow['id']);
            } catch (SqlException $e) {
                throw new errorException($this->translate('First you have to delete the cycles table, and then the calculation tables inside it'));
            }
        }
    }

    public function removeRows($remove, $isInnerChannel)
    {
        if ($this->tableRow['deleting'] === 'delete' || $isInnerChannel) {
            $this->loadRowsByIds($remove);
            foreach ($remove as $id) {
                if (!empty($this->tbl['rows'][$id])) {
                    $this->Totum->deleteCycle($id, $this->tableRow['id']);
                }
            }
        }

        parent::removeRows($remove, $isInnerChannel);
    }

    public function getUserCyclesCount()
    {
        return $this->countByParams((new FormatParamsForSelectFromTable())->where('creator_id',
            $this->User->getConnectedUsers())->params()['where']);
    }

    public function getUserCycleId()
    {
        return $this->getByParams((new FormatParamsForSelectFromTable())->field('id')->where('creator_id',
            $this->User->getConnectedUsers())->params(),
            'field');
    }

    protected function addRow($channel, $addData, $fromDuplicate = false, $addWithId = false, $duplicatedId = 0, $isCheck = false)
    {
        $addedRow = parent::addRow($channel, $addData, $fromDuplicate, $addWithId, $duplicatedId, $isCheck);

        if (!$fromDuplicate && !$isCheck) {
            $this->changeIds['rowOperations'][] = function () use ($addedRow, $channel) {
                $Cycle = Cycle::create($this->tableRow['id'], $addedRow['id'], $this->Totum);
                if ($channel === 'web' && $Cycle->getFirstTableId()) {
                    $action = new CalculateAction('=: linkToTable(table: ' . $Cycle->getFirstTableId() . '; cycle: ' . $addedRow['id'] . ')');
                    $action->execAction('addingRow', [], [], [], [], $this, 'add');
                }
            };
        }
        return $addedRow;
    }

    protected function duplicateRow($channel, $baseRow, $replaces, $addAfter)
    {
        $newRow = parent::duplicateRow($channel, $baseRow, $replaces, $addAfter);

        $this->Totum->getTable('calcstable_cycle_version')->actionDuplicate(
            ['cycle' => $newRow['id']],
            [
                ['field' => 'cycles_table', 'operator' => '=', 'value' => $this->tableRow['id']]
                , ['field' => 'cycle', 'operator' => '=', 'value' => $baseRow['id']]
            ]
        );

        $this->changeIds['rowOperationsPre'][] = function () use ($baseRow, $newRow) {
            $Log = $this->calcLog(['name' => 'DUPLICATE CYCLE']);
            Cycle::duplicate($this->tableRow['id'], $baseRow['id'], $newRow['id'], $this->Totum);
            $this->calcLog($Log, 'result', 'done');
        };

        $newRow = $this->model->getById($newRow['id']);

        return $newRow;
    }
}
