<?php
namespace sobernt\JqObject;
use DateTime;
use JsonSerializable;
use sobernt\JqObject\Exceptions\InstallJqException;
use sobernt\JqObject\Exceptions\InvalidArgumentException;
use \Jq;
use sobernt\JqObject\Exceptions\JQException;
use sobernt\JqObject\Exceptions\JsonException;


/**
 * Class JqObject
 * @package JqObject
 */
class JqObject implements JsonSerializable
{
    /**
     * @var Jq JQ object
     */
    private $jq;
    /**
     * @var array $cache - array of properties
     */
    private $cache;
    /**
     * @var bool $is_formatted - if true, values are formatted, else it's strings
     */
    private $is_formatted;
    /**
     * @var int $max_depth depth of recurse call
     */
    private $max_depth;
    /**
     * @var int $depth - depth of this class
     */
    private $depth;
    /**
     * @var bool $cached - true, if all object cached
     */
    private $cached;

    /**
     * JqObject constructor.
     * @param string $json - json for partial parse
     * @param bool $is_formatted - true if you went get formatted values
     * @param int $depth - depth of recursion
     * @param int $max_depth - max depth of recursion
     * @throws JsonException if has json validate errors
     * @throws InstallJqException
     */
    public function __construct(string $json,bool $is_formatted=true,$depth=0,$max_depth=255)
    {
        $this->cached=false;
        $this->depth = $depth;
        $this->max_depth = $max_depth;
        $this->is_formatted = $is_formatted;
        if(!class_exists('Jq')) throw new InstallJqException("Jq module not installed!",500);
        $this->jq =new Jq;
        $this->cache=[];

        set_error_handler(
            function ($errno, $errstr, $errfile, $errline)
            {
                throw new JQException($errstr.":".$errline,500);
            }
        );

        if(!$this->jq->load($json)){
            throw new JsonException("Json can not be parsed.",400);
        }
    }

    /**
     * @param int $max_depth - max recourse depth
     */
    public function setMaxDepth(int $max_depth): void
    {
        $this->max_depth = $max_depth;
    }

    /**
     * @return string raw source of object(json)
     */
    public function getSource(){
        return $this->jq->filter(".", JQ::RAW);
    }

    /**
     * raw query for Jq
     * @param $key - key for jq getter
     * @return mixed - value by it key
     */
    public function rawByKey($key){
        return $this->jq->filter($key);
    }

    /**
     * getter for other property
     * @param $name - name of property
     * @return mixed|null - php primitive, object or other, by property
     * @throws InvalidArgumentException - if argument non't in json structure
     * @throws JsonException - if isset json validation errors
     * @throws InstallJqException
     */
    public function __get($name)
    {
        if(array_key_exists($name,$this->cache)){
            return $this->cache[$name];
        }
        $result = $this->jq->filter(".$name", JQ::RAW);

        if($result=="null") {
            throw new InvalidArgumentException("structure element not found.",404);
        }

        if($this->depth > $this->max_depth){
            return null;
        }
        if ($this->isPrimitiveChild($result)) {
            $this->cache[$name] = $this->getPrimitive($result);
        } else
            if ($this->isArray($result)) {
                $result = $this->jq->filter(".$name");
                $this->cache[$name] = $this->arrayChild($result);
            } else {
                $this->cache[$name] = $this->getChildFromAssoc($result);
            }
        return $this->cache[$name];
    }

    /**
     * unpack values from array in cache. Uses for recursion call this object.
     * do this class cached.
     * do not recommend use this method, but it's really for changing work(cached)
     * structure without change Raw json
     * @param array $data - data for fake create recursive object from this class(or no this)
     * @throws JsonException if isset json errors
     * @throws InvalidArgumentException if you try set incorrect property of this structure
     * @throws InstallJqException if jq lib not installed
     */
    public function __invoke(array $data)
    {
        foreach ($data as $key=>$value){
            if(is_array($value)){
                if($this->isAssoc($value)) {
                    $source = $this->jq->filter(".$key",Jq::RAW);
                    if (!$source) {
                        throw new InvalidArgumentException("");
                    }
                    $this->cache[$key] = $this->getChildFromAssoc($source, $value);
                }
            } else{
                $this->cache[$key] = $this->getPrimitive($value);
            }
        }
        $this->cached = true;
    }

    /**
     * @return array - keys for this object
     * @throws InstallJqException
     * @throws InvalidArgumentException
     * @throws JsonException if json invalid
     */
    public function __sleep()
    {
        $this->cacheAll();
        return array_keys($this->cache);
    }

    /**
     * @return array
     * @throws InstallJqException
     * @throws InvalidArgumentException
     * @throws JsonException if json invalid
     */
    public function __debugInfo()
    {
        $this->cacheAll();
        return $this->cache;
    }

    /** cache all information of json object
     * @throws InstallJqException
     * @throws InvalidArgumentException
     * @throws JsonException if json invalid
     */
    public function cacheAll():void
    {
        //ohh, you went use all info..

        //is all cached?
        if($this->cached){
            return;
        }
        //and it's no cached..
        //so, go!
        $result = $this->jq->filter(".");
        foreach ($result as $key=>$value){
            if(is_array($value)){
                if($this->depth > $this->max_depth){
                    $result[$key] = null;
                    continue;
                }
                if(!$this->isAssoc($value)){
                    $result[$key] = $this->arrayChild($value);
                } else {
                    $result[$key] = $this->getChildFromAssoc($this->jq->filter(".$key", JQ::RAW),$value);
                }
            } else
                $result[$key] = $this->getPrimitive($result[$key]);

        }
        $this->cache = $result;
        $this->cached = true;
    }
    public function jsonSerialize():array
    {
        $this->cacheAll();
        return $this->cache;
    }

    /**
     * @return array
     * @throws InstallJqException
     * @throws InvalidArgumentException
     * @throws JsonException
     */
    public function as_array():array
    {
        $this->cacheAll();
        $cache_dump = $this->cache;
        $cache_dump = $this->serializeArray($cache_dump);
        return $cache_dump;
    }

    /**
     * @param array $serialize - array for recursive serialize
     * @return array
     * @throws \Exception
     */
    private function serializeArray(array $serialize){
        foreach ($serialize as $serialized_key => $serialized_value){
            if(is_array($serialized_value)){
                $serialize[$serialized_key] = $this->serializeArray($serialized_value);
            } else
                if(is_object($serialized_value)){
                    $serialize[$serialized_key] = $serialize[$serialized_key]->as_array();
                } else
                    if(is_string($serialized_value)){
                        $serialize[$serialized_key] = $this->format($serialized_value);
                    }
        }
        return $serialize;
    }

    /**
     * checking string on non-array and non-obj type
     * @param string $json - checked string
     * @return bool if true
     */
    private function isPrimitiveChild(string $json):bool
    {
        return !$this->isArray($json)&&
            !$this->isObject($json);
    }

    /**
     * checking string on non-array type
     * @param string $json - checked string
     * @return bool if true
     */
    private function isArray(string $json):bool
    {
        return preg_match("/^\[.*\]$/",$json)==1;
    }

    /**
     * checking string on non-obj type
     * @param string $json - checked string
     * @return bool if true
     */
    private function isObject(string $json):bool
    {
        return preg_match("/^\{.*\}$/",$json)==1;
    }

    /**
     * @param string $data - primitive value for formatter
     * @return \DateTime|float|int|string - formatted primitive
     * @throws \Exception if has formatting problems with date
     */
    private function format(string $data)
    {
        if(preg_match("/^[0-9]{1,19}$/",$data)){
            return intval($data);
        }
        if(is_double($data)){
            return doubleval($data);
        }
        //data in format YYYY-MM-DD OR YYYY-MM-DD hh:mm:ss
        if(preg_match('/^([\d]{4})-([\d]{1,2})-([\d]{1,2}) (([\d]{1,2}):([\d]{1,2}):([\d]{1,2}))?$/', $data, $data_matches)){
            $date = new DateTime();
            $date->setDate($data_matches[1],$data_matches[2],$data_matches[3]);

            if(sizeof($data_matches)==7){//with time
                $date->setTime($data_matches[5],$data_matches[6],$data_matches[7]);
            }

            return $date;
        }

        return $data;
    }

    /**
     * @param array $array - array for check
     * @return bool - true if checked is assoc array
     */
    private function isAssoc(array $array){
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * @param array $array child array
     * @return array with validated child's
     * @throws \Exception if child's has formatting problems with date
     */
    private function arrayChild(array $array):array
    {
        $result=[];
        foreach ($array as $key=>$value){
            if(is_array($value)) {
                if ($this->isAssoc($value)) {
                    $result[$key] = $this->getChildFromAssoc(json_encode($value), $value);
                } else {
                    $result[$key] = $this->arrayChild($value);
                }
            } else{
                $result[$key]=$this->getPrimitive($value);
            }
        }
        return $result;
    }

    /**
     * @param string $raw_json
     * @param array|null $array
     * @return JqObject - child for this
     * @throws InstallJqException
     * @throws InvalidArgumentException
     * @throws JsonException if child structure has errors
     */
    private function getChildFromAssoc(string $raw_json,array $array = null):JqObject
    {
        $res = new JqObject($raw_json,
            $this->is_formatted,
            $this->depth++,
            $this->max_depth);
        if(!is_null($array)) {
            $res($array);
        }
        return $res;
    }

    /**
     * @param $value - raw string of primitive
     * @return \DateTime|float|int|string - primitive type
     * @throws \Exception if has formatting problems with date
     */
    private function getPrimitive($value){
        return $this->is_formatted?$this->format($value):$value;
    }
}