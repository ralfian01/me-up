<?php

namespace AppConfig;

use MVCME\Request\Payload as BasePayload;

/**
 * Your own custom method that you use to validate your payload
 */
class Payload extends BasePayload
{
    /*
     * ------------------------------------
     * METHOD WRITING EXAMPLE
     * ------------------------------------ 
     * 
     * Format: protected function _<Your method name>
     * Example: protected function _MY_CUSTOM_METHOD()
     */

    // protected function _MY_CUSTOM_METHOD()
    // {
    //     $result = (object)[];
    //     $result->status = (bool) true;
    //     $result->fail_detail = [
    //         'reason' => 'Invalid payload reason',
    //         'expectation' => 'Expectation'
    //     ];
    //     // $result->break = !$result->status; // To stop validate next rules when status is false
    // }
}
