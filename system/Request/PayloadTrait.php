<?php

namespace MVCME\Request;

/**
 * The trait contains mvcme's built-in methods which are used to check the payload
 */
trait PayloadTrait
{

    /** 
     * @param array|object $payload Variable to check
     * @param string $key
     * @return object
     */
    protected static function _REQUIRED(&$payload, $key)
    {
        $result = (object)[];
        $result->status = isset($payload[$key]) && $payload[$key] != null;
        $result->fail_detail = [
            'reason' => 'This payload needs to be filled',
            'expectation' => 'Not NULL'
        ];
        // $result->break = !$result->status; // To stop validate next rules when status is false

        return $result;
    }

    /**
     * @param string $string String input
     * @return object
     */
    protected static function _BASE64(string $string)
    {
        $result = (object)[];
        $result->status = WritingFormat::isBase64($string);
        $result->fail_detail = [
            'reason' => 'This payload must be encoded',
            'expectation' => 'Encode using BASE64 scheme'
        ];
        // $result->break = !$result->status; // To stop validate next rules when status is false

        return $result;
    }

    /** 
     * @param string $string Date string input
     * 
     * @return object
     */
    protected static function _DATE_8601(string $string)
    {
        $result = (object)[];
        $result->status = WritingFormat::isDate($string, WritingFormat::ISO8601);
        $result->fail_detail = [
            'reason' => 'This payload must be written using datetime standard writing',
            'expectation' => 'Datetime writing using ISO 8601 standard (YYYY-MM-DD\THH:ii:ss+00:00)',
            'example' => '2022-12-12T01:01:01+00:00'
        ];
        // $result->break = !$result->status; // To stop validate next rules when status is false

        return $result;
    }

    /** 
     * @param string $string Date string input
     * 
     * @return object
     */
    protected static function _DATE_YMD(string $string)
    {
        $result = (object)[];
        $result->status = WritingFormat::isDate($string, WritingFormat::YMD);
        $result->fail_detail = [
            'reason' => 'This payload must be written using date standard writing',
            'expectation' => 'Datetime writing using Y-m-d standard (YYYY-MM-DD)',
            'example' => '2022-12-12'
        ];
        // $result->break = !$result->status; // To stop validate next rules when status is false

        return $result;
    }

    /** 
     * @param string $string String email input
     * 
     * @return object
     */
    protected static function _EMAIL(string $string)
    {
        $result = (object)[];
        $result->status = WritingFormat::isEmail($string);
        $result->fail_detail = [
            'reason' => 'This payload must be written using a valid email compose',
            'expectation' => 'None'
        ];
        // $result->break = !$result->status; // To stop validate next rules when status is false

        return $result;
    }

    /** 
     * @param string $string String segment domain input
     * 
     * @return object
     */
    protected static function _SEGMENT_DOMAIN(string $string)
    {
        $result = (object)[];
        $result->status = WritingFormat::isSegmentDomain($string);
        $result->fail_detail = [
            'reason' => 'This payload must be written using a valid segment domain write',
            'expectation' => 'Only accepts uppercase and lowercase letters, number, dash (-), and underscore (_)',
        ];
        // $result->break = !$result->status; // To stop validate next rules when status is false

        return $result;
    }

    /** 
     * @param string $string String domain input
     * 
     * @return object
     */
    protected static function _DOMAIN(string $string)
    {
        $result = (object)[];
        $result->status = WritingFormat::isDomain($string);
        $result->fail_detail = [
            'reason' => 'This payload must be written using a valid domain write',
            'expectation' => 'Only accept domain writing format as: <domain name><dot><domain extension>. Example: domain.com, domain.org, etc',
        ];
        // $result->break = !$result->status; // To stop validate next rules when status is false

        return $result;
    }

    /**  
     * @param mixed $int Integer input
     * 
     * @return object
     */
    protected static function _INT(mixed $int)
    {
        $result = (object)[];
        $result->status = WritingFormat::isInt($int);
        $result->fail_detail = [
            'reason' => 'This payload must be integer',
            'expectation' => 'Only accepts integers'
        ];
        $result->break = !$result->status; // To stop validate next rules when status is false

        return $result;
    }

    /**  
     * @param mixed $array Array input
     * 
     * @return object
     */
    protected static function _ARRAY(mixed $array)
    {
        $result = (object)[];
        $result->status = WritingFormat::isArray($array);
        $result->fail_detail = [
            'reason' => 'This payload must be filled in an array format',
            'expectation' => 'Only accepts arrays'
        ];
        $result->break = !$result->status; // To stop validate next rules when status is false

        return $result;
    }

    /**  
     * @param mixed $booblean Boolean input
     * 
     * @return object
     */
    protected static function _BOOLEAN(mixed $boolean)
    {
        $result = (object)[];
        $result->status = WritingFormat::isBool($boolean);
        $result->fail_detail = [
            'reason' => 'This payload must be filled in boolean format',
            'expectation' => 'Only accepts true or false'
        ];
        $result->break = !$result->status; // To stop validate next rules when status is false

        return $result;
    }

    /** 
     * @param string $string String input
     * @param int $length Max length of $string
     * @return object
     */
    protected static function _MAXLENGTH(string $string, int $length = 0)
    {
        $result = (object)[];
        $result->status = strlen($string) <= $length;
        $result->fail_detail = [
            'reason' => "This payload exceeds the character length limit",
            'expectation' => "Character length must be less than or equal to {$length} characters",
        ];
        // $result->break = !$result->status; // To stop validate next rules when status is false

        return $result;
    }

    /** 
     * @param string $string String input
     * @param int $length Min length of $string
     * @return object
     */
    protected static function _MINLENGTH(string $string, int $length = 0)
    {
        $result = (object)[];
        $result->status = strlen($string) >= $length;
        $result->fail_detail = [
            'reason' => "This payload is less than the minimum character length",
            'expectation' => "Character length must be more than or equal to {$length} characters",
        ];
        // $result->break = !$result->status; // To stop validate next rules when status is false

        return $result;
    }

    /** 
     * @param mixed $array Array input
     * @param int $length Max item of $array
     * @return object
     */
    protected static function _MAXITEM(mixed $array, int $length = 1)
    {
        $result = (object)[];
        $result->status = is_array($array) ? count($array) <= $length : true;
        $result->fail_detail = [
            'reason' => "This payload exceeds the length limit of array items",
            'expectation' => "Array item must be less than or equal to {$length}",
        ];
        // $result->break = !$result->status; // To stop validate next rules when status is false

        return $result;
    }

    /** 
     * @param array $array Array input
     * @param int $length Max item of $array
     * @return object
     */
    protected static function _MINITEM(array $array, int $length = 1)
    {
        $result = (object)[];
        $result->status = count($array) >= $length;
        $result->fail_detail = [
            'reason' => "This payload is less than the minimum of array items",
            'expectation' => "Array item must be more than or equal to {$length}",
        ];
        // $result->break = !$result->status; // To stop validate next rules when status is false

        return $result;
    }

    /** 
     * @param string $string String input
     * @param mixed $enums Enum
     * @return object
     */
    protected static function _ENUM(string $string, $enums)
    {
        $result = (object)[];
        $result->status = in_array($string, preg_split('/[,, ]/', $enums));
        $result->fail_detail = [
            'reason' => "This payload accepts only one of the available enum options",
            'expectation' => "Select one of the enums ({$enums})",
        ];
        // $result->break = !$result->status; // To stop validate next rules when status is false

        return $result;
    }

    /** 
     * @param array|object $file File input
     * @return object
     */
    protected static function _FILE($file)
    {

        $checkFile = function ($fileObj) {
            return is_object($fileObj) && method_exists($fileObj, 'getName');
        };

        $status = true;

        // Check $file is array or object
        if (is_array($file)) {
            foreach ($file as $key => $val) {

                $check = $checkFile($file[$key]);
                if (!$check) $check;
            }
        } else {
            $check = $checkFile($file);
            if (!$check) $check;
        }
        $result = (object)[];
        $result->status = $status;
        $result->fail_detail = [
            'reason' => "This payload can only be filled by files",
            'expectation' => "Fill the payload with files"
        ];
        $result->break = !$result->status; // To stop validate next rules when status is false

        return $result;
    }

    /** 
     * @param array|object $file File input
     * @param mixed $support Enum
     * @return object
     */
    protected static function _FILE_ACCEPT($file, $support)
    {
        $checkFile = function ($fileObj, $support) {

            $parts = explode('.', $fileObj->getName());
            $ext = strtolower(end($parts));

            $return = new \stdClass();
            $return->status = true;

            if (!in_array($ext, preg_split('/[,, ]/', $support))) {

                $return->status = false;
                $return->detail = [
                    'name' => $fileObj->getName(),
                    'extension' => $ext
                ];
            }

            return $return;
        };

        $invalidFiles = [];
        $status = true;

        // Check $file is array or object
        if (is_array($file)) {
            foreach ($file as $key => $val) {

                $check = $checkFile($file[$key], $support);
                if (!$check->status) {

                    $status = $check->status;
                    $invalidFiles[] = $check->detail;
                }
            }
        } else {
            $check = $checkFile($file, $support);
            if (!$check->status) {

                $status = $check->status;
                $invalidFiles[] = $check->detail;
            }
        }
        $result = (object)[];
        $result->status = $status;
        $result->fail_detail = [
            'reason' => "This payload does not accept uploaded file extensions",
            'expectation' => "Only accepts file extensions ({$support})",
            'invalid_files' => $invalidFiles
        ];
        // $result->break = !$result->status; // To stop validate next rules when status is false

        return $result;
    }

    /** 
     * @param array|object $file File input
     * @return object
     */
    protected static function _SINGLE_FILE($file)
    {
        $result = (object)[];
        $result->status = !is_array($file);
        $result->fail_detail = [
            'reason' => "This payload can only be filled by single file",
            'expectation' => "Fill the payload with only single file, not in the form of an array"
        ];
        // $result->break = !$result->status; // To stop validate next rules when status is false

        return $result;
    }

    /** 
     * @param string $number Call Number input
     * @param string $region_code Region Code input
     * @return object
     */
    protected static function _CALL_NUMBER($number, $region_code = null)
    {
        $result = (object)[];
        $result->status = WritingFormat::isCallNumber($number, $region_code);
        $result->fail_detail = [
            'reason' => "This payload can only be filled in with call number writing format",
            'expectation' => "{$region_code}<call_number>"
        ];
        // $result->break = !$result->status; // To stop validate next rules when status is false

        return $result;
    }

    /** 
     * @param array $array Array input
     * @param string $required_key Required key
     * @return object
     */
    protected static function _NESTED_ARRAY_HAS($array, $required_key = [])
    {

        $result = (object)[];

        if (!isset($array[0])) {
            $result->status = false;
            $result->fail_detail = [
                'reason' => "This payload is written in an incorrect array format",
                'expectation' => "The payload must be written in array format, example: array[0]['key']"
            ];
            // $result->break = !$result->status; // To stop validate next rules when status is false
            return $result;
        }

        $required_key = explode(',', str_replace(' ', '', $required_key));

        foreach ($array as $arrKey => $arrVal) {

            foreach ($required_key as $rqVal) {

                if (!isset($arrVal[$rqVal])) {

                    $result->status = false;
                    $result->fail_detail = [
                        'reason' => "This json does not have required key at index {$arrKey}",
                        'expectation' => "The payload must have the required key like (\"" . implode('","', $required_key) . "\")"
                    ];
                    // $result->break = !$result->status; // To stop validate next rules when status is false
                    return $result;
                }
            }
        }

        $result->status = true;
        $result->fail_detail = [];
        // $result->break = !$result->status; // To stop validate next rules when status is false
        return $result;
    }
}
