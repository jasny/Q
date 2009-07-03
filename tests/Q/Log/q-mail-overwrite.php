<?php
namespace Q;

/**
 * Overwrites mail function in Q namespace.
 * 
 * @param string $to
 * @param string $subject
 * @param string $message
 * @param string $additional_headers
 * @param string $additional_parameters
 * @return boolean
 */
function mail($to, $subject, $message, $additional_headers=null, $additional_parameters=null)
{
    $GLOBALS['_mail_'][] = compact('to', 'subject', 'message', 'additional_headers', 'additional_parameters');
}
?>