<?php

namespace Eva\Tools;
use PHPExcel_IOFactory;

class Xlsx
{

    private $filepath;
    private $sheet;
    private $count;
    private $heighest;

    public function __construct($filepath, $sheet = 0)
    {
        $this->filepath = $filepath;

        $type = PHPExcel_IOFactory::identify($this->filepath);
        $objReader = PHPExcel_IOFactory::createReader($type);
        $objPHPExcel = $objReader->load($this->filepath);

        $this->sheet = $objPHPExcel->getSheet($sheet);
        $this->count = 1;
        $this->heighest = $this->sheet->getHighestRow();
    }

    public function getNextRow()
    {

        if ($this->count > $this->heighest) {
            return FALSE;
        }

        $row = array();
        $highestColumn = $this->sheet->getHighestColumn();

        for ($i = \PHPExcel_Cell::columnIndexFromString('A') - 1, $il = \PHPExcel_Cell::columnIndexFromString($highestColumn); $i < $il; $i++) {
            $row [] = trim($this->sheet->getCell(\PHPExcel_Cell::stringFromColumnIndex($i) . $this->count)->getValue());
        }

        $this->count++;
        return $row;

    }

    public function getAllRows()
    {

        $rows = array();
        while (($row = $this->getNextRow()) !== FALSE) {
            $rows [] = $row;
        }
        return $rows;

    }

    /**
     * @return \PHPExcel_Worksheet
     */
    public function getSheet()
    {
        return $this->sheet;
    }

    /**
     * @param \PHPExcel_Worksheet $sheet
     */
    public function setSheet($sheet)
    {
        $this->sheet = $sheet;
    }


}