<?php

namespace MVCME\Request;

/**
 * Class to process payload for API requests
 * 
 * This class provides methods to validate various types of payloads commonly used in API requests.
 * Each validation method corresponds to a specific payload validation rule.
 * 
 * Validate payload based on conventions:
 * - 'required' Mean a payload is required 
 * - 'base64' Means to to Check if a string is Base64 encoded.
 * - 'date_8601' Means to Check if a string is in ISO 8601 date format.
 * - 'date_ymd' Means to Check if a string is in Y-m-d date format.
 * - 'email' Means to Check if a string is a valid email address.
 * - 'segment_domain' Means to Check if a string is a valid segment of a domain.
 * - 'domain' Means to Check if a string is a valid domain.
 * - 'int' Means to Check if a value is an integer.
 * - 'array' Means to Check if a value is an array.
 * - 'maxlength[..]' Means to Check if a string doesn't exceed a maximum length.
 * - 'minlength[..]' Means to Check if a string meets a minimum length.
 * - 'maxitem[..]' Means to Check if an array doesn't exceed a maximum number of items.
 * - 'minitem[..]' Means to Check if an array meets a minimum number of items.
 * - 'enum[.., ..]' Means to mixed Check if a string is one of the specified enum values.
 * - 'file' Means to Check if a value is a file or an array of files.
 * - 'file_accept[.., ..]' Means to Check if a file or an array of files has an accepted extension.
 * - 'single_file' Means to Check if a value is a single file.
 * - 'call_number[..]' Means to Check if a string is a valid call number.
 * - 'nested_array_has[..]' Means to Check if a nested array has a particular key at each repeated index that required
 * - 'array_has[..]' Means to Check if an array has a particular key that required
 * 
 * Example Usage:
 *
 * $payloadRules = [
 *     'project_id' => ['required', 'base64'],
 *     'detail' => ['required'],
 *     'release_date' => ['date_8601']
 * ];
 */
class Payload extends FormDataParser
{
    use PayloadTrait;

    private $validationData = null;
    private $invalidPayloadFunc = null;
    private $validPayloadFunc = null;

    /**
     * Function to setup validation payload and rules
     * 
     * @param array $payload Payload to check
     * @param array|object $rules_or_file Payload rules or payload file
     * @param array $rules Payload rules
     * @return $this
     */
    public function setValidationData(array $payload, $rules_or_file, array $rules = null)
    {

        // Check if $rules_or_file include file
        foreach ($rules_or_file as $key => $val) {
            if (is_array($rules_or_file[$key])) {
                foreach ($rules_or_file[$key] as $fKey => $fVal) {
                    if (method_exists($rules_or_file[$key][$fKey], 'getName')) $payload[$key][$fKey] = $rules_or_file[$key][$fKey];
                }
            } else {
                if (method_exists($rules_or_file[$key], 'getName')) $payload[$key] = $rules_or_file[$key];
            }
        }

        $this->validationData = [
            'payload' => $payload,
            'rules' => $rules ?? $rules_or_file
        ];

        return $this;
    }

    /**
     * Function to set callback when validation is success and pass callback parameter
     * 
     * @param function $function Callback function
     * @return $this
     */
    public function validationSuccess($function)
    {

        $this->validPayloadFunc = $function;
        return $this;
    }

    /**
     * Function to set callback when validation is failed and pass callback parameter
     * 
     * @param function $function Callback function
     * @return $this
     */
    public function validationFail($function)
    {

        $this->invalidPayloadFunc = $function;
        return $this;
    }

    /**
     * Function to validate payload
     * @return $this
     */
    public function validate()
    {
        if ($this->validationData == null)
            return throw new \Error('You must set validation data using method setValidationData() before performing checks');

        $payload = $this->validationData['payload'];
        $rules = $this->validationData['rules'];

        $invalidPayloads = [];

        // Start check payload
        foreach ($rules as $key => $rules) {

            $reasons = [];

            foreach ($rules as $val) {

                $extract = $this->extractRuleValue($val);
                $extract->rule = "_" . strtoupper($extract->rule);

                if (!method_exists($this, $extract->rule)) {
                    throw new \Error("The method ({$extract->rule}) to validate the payload rules is not available.");
                }

                if ($extract->rule != '_REQUIRED') {

                    if (isset($payload[$key])) {

                        $check = $this->{$extract->rule}($payload[$key], $extract->value);
                        if (!$check->status)
                            $reasons[] = $check->fail_detail;

                        if (isset($check->break) && $check->break)
                            break;
                    }
                } else {

                    $check = $this->_REQUIRED($payload, $key, $extract->value);
                    if (!$check->status)
                        $reasons[] = $check->fail_detail;

                    if (isset($check->break) && $check->break)
                        break;
                }
            }

            if (count($reasons) >= 1)
                $invalidPayloads[] = [
                    'payload_name' => $key,
                    'reasons' => $reasons
                ];
        }

        if (
            count($invalidPayloads) >= 1
            && is_callable($this->invalidPayloadFunc)
        ) return call_user_func($this->invalidPayloadFunc, $invalidPayloads);

        if (is_callable($this->validPayloadFunc)) return call_user_func($this->validPayloadFunc);
    }

    /** 
     * Function to extract value from rule
     * @param string $rule
     * @return object
     */
    private function extractRuleValue(string $rule = '')
    {
        $match = explode("[", $rule);

        $extracted = new \stdClass();
        $extracted->value = isset($match[1]) ? substr($match[1], 0, -1) : '';
        $extracted->rule = $match[0];
        return $extracted;
    }
}
