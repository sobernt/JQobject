<?php

use sobernt\JqObject\Exceptions\InvalidArgumentException;

require("vendor/autoload.php");

    $obj = new sobernt\JqObject\JqObject("{
        \"testkey\":\"testval\",
        \"testarray\":[
            \"testsimplearrayval1\",
            \"testsimplearrayval2\"
        ],
        \"testcompositearray\":[
            \"testcompositearrayval1\",
            {
                 \"testcompositearray2key\": \"testcompositearray2value\"
            }
        ],
         \"testobject\":{
                 \"testobjectkey\": \"testobjectval\",
                 \"testobjectintkey\": \"1\"
        }
    }");
    echo("\ntestkey:");
    var_dump($obj->testkey);
    echo("\ntestarray:");
    var_dump($obj->testarray);
    echo("\ntestcompositearray:");
    var_dump($obj->testcompositearray);
    echo("\ntestcompositearray[1]:");
    var_dump($obj->testcompositearray[1]);
    echo("\ntestobject:");
    var_dump($obj->testobject);
    echo("\ntestobject->testobjectkey:");
    var_dump($obj->testobject->testobjectkey);
    echo("\n->testobject->testobjectintkey:");
    var_dump($obj->testobject->testobjectintkey);
    echo("\nobj:");
    var_dump($obj);
    echo("\nobj->as_array:");
    var_dump($obj->as_array());
    echo("\njson_encode(obj):");
    var_dump(json_encode($obj));


    try{
        var_dump($obj->tst);
    }catch (InvalidArgumentException $e){
        var_dump($e);
    }

    try{
        $obj = new \sobernt\JqObject\JqObject("testval");
    }catch (Exception $e){
        var_dump($e);
    }