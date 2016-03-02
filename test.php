<?php
///**
// * Magento
// *
// * NOTICE OF LICENSE
// *
// * This source file is subject to the Open Software License (OSL 3.0)
// * that is bundled with this package in the file LICENSE.txt.
// * It is also available through the world-wide-web at this URL:
// * http://opensource.org/licenses/osl-3.0.php
// * If you did not receive a copy of the license and are unable to
// * obtain it through the world-wide-web, please send an email
// * to license@magento.com so we can send you a copy immediately.
// *
// * DISCLAIMER
// *
// * Do not edit or add to this file if you wish to upgrade Magento to newer
// * versions in the future. If you wish to customize Magento for your
// * needs please refer to http://www.magento.com for more information.
// *
// * @category    Mage
// * @package     Mage
// * @copyright  Copyright (c) 2006-2015 X.commerce, Inc. (http://www.magento.com)
// * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
// */
//
//// Change current directory to the directory of current script
//chdir(dirname(__FILE__));
//
//require '../app/bootstrap.php';
//require '../app/Mage.php';
//
//if (!Mage::isInstalled()) {
//    echo "Application is not installed yet, please complete install wizard first.";
//    exit;
//}
//
//// Only for urls
//// Don't remove this
//$_SERVER['SCRIPT_NAME'] = str_replace(basename(__FILE__), 'index.php', $_SERVER['SCRIPT_NAME']);
//$_SERVER['SCRIPT_FILENAME'] = str_replace(basename(__FILE__), 'index.php', $_SERVER['SCRIPT_FILENAME']);
//
//Mage::app('admin','store')->setUseSessionInUrl(false);
////Mage::getConfig()->init()->loadEventObservers('admin');
//
//umask(0);

class Convert_CSV
{

    private $_csvFile;
    private $path;

    private $_enclosure;
    private $_delimiter;

    private $default_attribute = array('attribute_set' => 'Scene7', 'store' => 'bradford_uk','category_ids'=> 384);
    private $column_index;  // format:  array(column_name => index)

    public function __construct($delimiter = ',',$enclosure = '"')
    {
        $this->_csvFile = new Varien_File_Csv();
        $this->_enclosure = $enclosure;
        $this->_delimiter = $delimiter;
        $this->_csvFile->setDelimiter($delimiter);
        $this->_csvFile->setEnclosure($enclosure);
        $this->path = Mage::getBaseDir('var').'/convert/';
    }

    public function convert($fileName,$newFileName,$mapColumn = array())
    {
        $temp_data = $this->_csvFile->getData($this->path.$fileName);

        $this->initMapColumn($temp_data[0],$mapColumn);
        $convert_data = array();
        $convert_data[0] = array_keys($this->column_index);

        $temp_data_size = count($temp_data);
        for ($index = 1; $index < $temp_data_size; $index++){
            $convert_data[$index] = $this->handleRowData($temp_data[$index]);;
            unset($temp_data[$index]);
        }

        $count = $this->_csvFile->saveData($this->path.$newFileName,$convert_data);
    }




    private function initMapColumn($row,$map){
        foreach ($map as $key => $value) {
            $index = $this->getIndexForConcat($key,$row);

            if($index !== false){
                $this->column_index[$value] = $index;

            }
        }

        $count = count($row);
        foreach($this->default_attribute as $attribute => $value){
            $key = array_search($attribute,array_keys($this->column_index));
            if($key === false || $key == null){
                $this->column_index[$attribute] = $count;
                $count++;
            }
        }
    }

    private function handleRowData(&$row){
        $res_array = array();
        foreach($this->column_index as $column => $indexes){
            $res_columns = $this->concatTextAttribute($indexes,$row);
            $res_array[] = $this->handleColumn($res_columns,$column);
        }
        return $res_array;
    }

    private function getIndexForConcat($string,$row){
        $array = explode(',',$string);
        $res = array();
        if(count($array)>0){
            foreach($array as $value){
                $temp = array_search($value,$row);
                if($temp !== null){
                    $res[] = $temp;
                }
            }
            if(count($res) == 0) return false;
            return $res;
        }
        return false;
    }

    private function handleColumn(&$res_columns,$column_name){
        if(!isset($res_columns)){
            $key = array_search($column_name,array_keys($this->default_attribute));
            if($key !== null){
                return $this->default_attribute[$column_name];
            }
        }
        return $res_columns;
    }

    private function concatTextAttribute($indexes,$row){
        $res_columns = "";
        if(count($indexes)>1){
            foreach($indexes as $index){
                $res_columns = $res_columns.$row[$index]." ";
            }
        }else{
            $res_columns = $row[$indexes[0]];
        }
        return $res_columns;

    }


}

$convertCSV = new Convert_CSV(',','"');
$fileName = "bex_uk.csv";
$newFileName = "import_magmi.csv";
// $map = array(ColumnName => attribute_code)

$map = array(
    "Price"=>"price",
    "Description,Product Bullets"=>"description",
    "Product Name"=>"meta_title",
    "Product ID"=>"scene7_media_images",
    "Keywords" => "meta_keyword",
    "Product Short Name,Portals Short Description"=>"name",
    "Guarantee"=>"warranty",
    "Measurements"=>"measurements",
    "Short Description"=>"short_description",
    "MF Product ID"=>"sku",
    );



$convertCSV->convert($fileName,$newFileName,$map);




