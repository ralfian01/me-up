<?php

namespace MVCME\Request;

class WritingFormat
{

    /** Function to check if payload writing format in base64 or not
     * 
     * @param string $string String input
     * 
     * @return boolean
     */
    public static function isBase64(string $string)
    {

        return base64_encode(base64_decode($string, true)) === $string;
    }

    // Date Writing format
    public const ISO8601 = \DateTime::ATOM; // ISO8601 standard
    public const YMD = 'Y-m-d'; // Y-m-d standard

    /** Function to check if string written using date standard
     * 
     * @param string $string Date string input
     * @param mixed $format Date writing format
     * 
     * @return boolean
     */
    public static function isDate(string $string, $format)
    {

        $dateTime = \DateTime::createFromFormat($format, $string);
        return $dateTime && $dateTime->format($format) === $string;
    }

    /** Function to check if string written in valid email or not
     * 
     * @param string $string Email string input
     * 
     * @return boolean
     */
    public static function isEmail(string $string)
    {

        return filter_var($string, FILTER_VALIDATE_EMAIL);
    }

    /** Function to check if string written in valid segment domain
     * 
     * @param string $string Segment domain string input
     * 
     * @return boolean
     */
    public static function isSegmentDomain(string $string)
    {

        return preg_match('/^[0-9A-Za-z_-]+$/', $string) === 1;
    }

    /** Function to check if string written in valid domain
     * 
     * @param string $string Domain string input
     * 
     * @return boolean
     */
    public static function isDomain(string $string)
    {

        return filter_var($string, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
    }

    /** Function to check if input is integer
     * 
     * @param mixed $input Input integer
     * 
     * @return boolean
     */
    public static function isInt(mixed $input)
    {

        if (preg_match('/^[0-9]+$/', $input))
            $input = intval($input);

        return is_int($input);
    }

    /** Function to check if input is integer
     * 
     * @param mixed $input Input
     * 
     * @return boolean
     */
    public static function isArray(mixed $input)
    {

        return is_array($input);
    }

    /** Function to check if input is boolean
     * 
     * @param mixed $input Input
     * 
     * @return boolean
     */
    public static function isBool(mixed $input)
    {

        return is_bool($input);
    }

    /** Function to check if input is call number
     * 
     * @param mixed $input Input
     * @param string|int $region_code Region code
     * 
     * @return boolean
     */
    public static function isCallNumber(mixed $input, string|int $region_code = null)
    {

        if ($region_code == null) {
            return true;
        } else {
            if (substr($input, 0, 2) == $region_code)
                return true;

            return false;
        }
    }
}
