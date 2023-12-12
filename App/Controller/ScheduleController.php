<?php

namespace App\Controller;

use App\Model\ScheduleModel;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ScheduleController
{
    private string $filename = '';

    private $excel = '';
    private $reader = '';
    private $spreadsheet = '';
    private $worksheet = '';
    private $groups = '';

    private $schedule = [];

    public function __construct(string $filename)
    {
        $this->filename = $filename;

        $this->excel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        $this->init();

    }

    private function init(): void
    {
        $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($this->filename);

        $this->reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);

        $this->reader->setReadDataOnly(true);

    }

    public function setReaderCoordinates(int $start, int $end, array $range): void
    {
        $this->spreadsheet =  $this->reader->setReadFilter(new ReadScheduleFilter($start, $end, $range));
    }

    public function load(): void
    {
        $this->spreadsheet = $this->reader->load($this->filename);
    }

    private function makeActiveSheet(): void
    {
        $this->worksheet = $this->spreadsheet->getActiveSheet();
    }

    public function getActiveSheet(): Worksheet
    {
        return $this->worksheet;
    }

    public function setSearchGroups(array $groups): bool
    {
        if (empty($groups))
            return false;

        $this->groups = $groups;

        return true;
    }

    public function getSearchGroups(): string
    {
        return '/([а-яА-Я+]{1,4}\-)+([а-яА-Я+\d+]{1,4}\-)+([а-яА-Я+])/is';
    }


    private function processFill() {
        $this->makeActiveSheet();

        $result = [];

        $scheduleTmp = [];
        $arrTmp = [];
        $i = 0;
        $j = 0;
        $k = -1;
        $lastCol = '';
        $endRowCounting = false;

        foreach ($this->worksheet->getColumnIterator() as $row) {
            $cellIterator = $row->getCellIterator();

            $cellIterator->setIterateOnlyExistingCells(True); // This loops through all cells,
            foreach ($cellIterator as $cell) {

                if ($lastCol != $cell->getColumn()) {
                    $k = -1;
                    $endRowCounting = false;
                }

                if ($cell->getColumn() == 'B') {
                    if ($cell->getValue() === null || preg_match('/([\d+]{2}\.[\d+]{2}\-*)/s', $cell->getValue()) != false) {
                        if($cell->getValue() != null) {
                            $scheduleTmp[]= $cell->getValue();
                        } else {
                            $result['time'][] = $scheduleTmp;
                            $scheduleTmp = [];
                        }
                    }

                } else {
                    if ($lastCol == 'B') { // hook
                        $result['time'][] = $scheduleTmp;
                        $scheduleTmp = [];
                    }
                    //var_dump($cell->getValue(), preg_match($this->getSearchGroups(), $cell->getValue()));
                    if ( $cell->getRow() == 4 && preg_match($this->getSearchGroups(), $cell->getValue() ?? '') !== 0) {

                        $result['groups'][] = [
                            'id' => $i,
                            'name' => $cell->getValue(),
                            'coordinates' => [$cell->getCoordinate(), $cell->getColumn(), $cell->getRow()],
                            'schedule' => [],
                        ];

                        $i++;
                    } else {
                        if ($endRowCounting === true || preg_match('/([\d+]{1,2}\.[\d+]{2}+)/s', $cell->getValue() == null ? '' : $cell->getValue()) != false) {
                            $j = 0; // Обновляем так же номер занятия

                            if (empty($arrTmp)) {
                                $result['groups'][end(array_keys($result['groups']))]['schedule'][$cell->getValue()] = [];
                                $endRowCounting = false;
                            }
                            else {
                                $key = array_key_last($result['groups'][end(array_keys($result['groups']))]['schedule']);
                                $result['groups'][end(array_keys($result['groups']))]['schedule'][$key] = end($arrTmp);
                                $result['groups'][end(array_keys($result['groups']))]['schedule'][$cell->getValue()] = [];

                                if ($endRowCounting === true)
                                    $endRowCounting = null;
                            }

                            $arrTmp = [];
                            $k++;

                        } else {
                            if (empty($arrTmp)) {
                                $current = $result['groups'][end(array_keys($result['groups']))]['schedule'];
                                $arrTmp = [array_key_last($current) => end($current)];
                            }

                            if (array_key_exists($k, $result['time'] ?? []) != false && array_key_exists($j, $result['time'][$k] ?? []) != false) {
                                $arrTmp[array_key_last($arrTmp)][] = [$result['time'][$k][$j], $cell->getValue()];
                            }
                            else {
                                $endRowCounting = true;
                            }


                            $current = [];
                            $j++;
                        }
                    }
                }
                $lastCol = $cell->getColumn();

            }

        }

        return $result;
    }

    public function fill()
    {
        $result = $this->processFill();

        if (empty($result))
            return false;

        foreach ($result['groups'] as $k => $group) {
            $model = new ScheduleModel();
            $model->setID($group['id']);
            $model->setName($group['name']);
            $model->setGroupCoordinates($group['coordinates']);
            $model->setSchedule($group['schedule']);

            $this->schedule[] = $model;
        }

    }

    public function getScheduleByGroupName(string $name): ScheduleModel|null
    {
        foreach ($this->schedule as $schedule) {
            if ($schedule->getName() == $name)
                return $schedule;
        }

        return null;
    }

    public function getScheduleByGroupID(int $id): ScheduleModel|null
    {
        foreach ($this->schedule as $schedule) {
            if ($schedule->getID() == $id)
                return $schedule;
        }

        return null;
    }

    public function getScheduleByGroupIDAndDate(int $id, ?string $date = null): ScheduleModel|null|array
    {
        $result = $this->getScheduleByGroupID($id);

        if ($date === null)
            return $result;

        return $this->getScheduleByDate($result, $date);

    }

    public function getAllGroups(): array
    {
        $result = [];

        foreach ($this->schedule as $schedule) {
            $result[$schedule->getID()] = $schedule->getName();
        }

        return $result;
    }


    public function getScheduleByDate(?ScheduleModel $scheduleModel, string $date): array|null
    {
        if ($scheduleModel === null)
            return null;

        foreach ($scheduleModel->getSchedule() as $scheduleDate => $schedule) {
            if ($scheduleDate == $date)
                return $schedule;
        }

        return null;
    }
}

/**  Define a Read Filter class implementing \PhpOffice\PhpSpreadsheet\Reader\IReadFilter  */
class ReadScheduleFilter implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter
{
    private $startRow = 0;
    private $endRow   = 0;
    private $columns  = [];

    /**  Get the list of rows and columns to read  */
    public function __construct($startRow, $endRow, $columns) {
        $this->startRow = $startRow;
        $this->endRow   = $endRow;
        $this->columns  = $columns;
    }

    public function readCell($columnAddress, $row, $worksheetName = '') {
        //  Only read the rows and columns that were configured
        if ($row >= $this->startRow && $row <= $this->endRow) {
            if (in_array($columnAddress,$this->columns)) {
                return true;
            }
        }
        return false;
    }
}