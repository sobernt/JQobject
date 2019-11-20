**Install**

do install instructions for requrement package:
_https://github.com/kjdev/php-ext-jq_

install this package via composer:

`composer require sobernt/JqObject`

**Usage**

init object with minimal params:

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

full constructor:

`__construct(string $json,bool $is_formatted=true,$depth=0,$max_depth=255)`

set `$is_formated=false` for get all primitives as string.
set `$max_depth = 500` for reconfigure object max recursion level = 500

throws `InstallJqException` if jq not installed

throws `JsonException` if json can't be parse

throws `JQException` on all other service errors\php warnings in class.


you can use this as default object from json_encode, but json parse by parts if you use this syntax:

    $obj->testobject
    
this example return array:

    $obj->testarray
    
this example returns object:
  
    $obj->testcompositearray[1]
    
this example returns string if `$is_formated=false` else (int)1:
  
    $obj->testobject->testobjectintkey
  
throws `InvalidArgumentException` if you call object not founded in json