<?php

/**
 * BotDetector prevents spam form submissions by requiring a unique token, fetched 
 * with Ajax, to be submitted via a hidden field (requires JavaScript to be enabled)
 * 
 * BotDetector Currently depends on jQuery, and only supports forms submitted via POST
 * 
 * Usage:
 * 
 * 1. Change the $secretKey property to a string of your own choosing (preferably 
 *    something random that won't be easily guessed)
 * 2. Include BotDetector.php and initialize the BotDetector class
 * 3. After your form, call the insertToken() method and pass it the form ID and 
 *    relative path to BotDetectorAjax.php
 * 4. When the form is submitted, call the validate() method within a try...catch block
 * 
 * How it works:
 * 
 * 1. When the form is requested, the current timestamp is stored a session variable.
 * 2. When the key is requested via Ajax, BotDetector returns this timestamp, 
 *    salted & hashed (difficult to fake)
 * 3. JavaScript inserts the key into a hidden input field in the form
 * 4. When the form is submitted, if the key is not present or does not match the 
 *    timestamp in the session, validation will fail.
 *
 * Website: https://github.com/theodorejb/SpambotDetector
 * Updated: 2013-08-01
 *
 * @author Theodore Brown
 * @version 0.9.0
 */
class BotDetector {

    private $keyInputName = "BotDetectorKey";
    private $timestampSessionName = "BotDetectorTime";
    private $loadTimestamp;
    private $secretKey = 'Change this to a secret string of your choosing';
    private $instance; // used to salt the timestampSessionName in case more than one BotDetector instance is needed on a page

    public function __construct($instance = NULL) {

        $instanceRegEx = '/^[a-zA-Z\d]+$/';
        if ($instance != NULL && preg_match($instanceRegEx, $instance)) {
            $this->instance = $instance;
            $this->timestampSessionName = $this->timestampSessionName . $this->instance;
        }

        if (session_id() == '') {
            // no session has been started; try starting it
            if (!session_start())
                throw new Exception("Unable to start session");
            else
                session_regenerate_id();
        }

        // if a session timestamp isn't set, initialize it
        if (!isset($_SESSION[$this->timestampSessionName]))
            $_SESSION[$this->timestampSessionName] = time();

        // store the session timestamp value in a class property
        $this->loadTimestamp = $_SESSION[$this->timestampSessionName];
    }

    /**
     * Runs an Ajax script to fetch the key and embed it in the form
     * 
     * @param String $formId the ID of the form to validate
     * @param String $pathToAjaxResponseFile A relative path from the form page to BotDetectorAjax.php
     */
    public function insertToken($formId, $pathToAjaxResponseFile) {
        if (!empty($this->instance))
            $data = "data: {instance: '$this->instance'}";
        else
            $data = '';

        echo <<<_SCRIPT
        <script>
            $.ajax({
                url: "$pathToAjaxResponseFile",
                type: "POST",
                dataType: "text",
                $data
            }).done(function(data) {
                var form = document.getElementById('$formId');
                var keyInputElement = document.createElement('input');
                keyInputElement.setAttribute('type', 'hidden');
                keyInputElement.setAttribute('name', '$this->keyInputName');
                keyInputElement.setAttribute('value', data);
                $(form).append(keyInputElement);
            }).fail(function (jqxhr, textStatus, error) {
                var err = textStatus + ', ' + error;
                console.log("Request Failed: " + err);
            });
        </script>
_SCRIPT;
    }

    /**
     * Generates a valid hash key to include in a hidden form field via JavaScript
     */
    public function getValidKey() {
        $timestamp = $this->loadTimestamp;
        $salt = $this->secretKey;
        return sha1($timestamp . $salt);
    }

    /**
     * Checks for a POSTed token matching the hashed/salted key
     * 
     * @return boolean TRUE if valid, otherwise FALSE
     * @throws Exception if the token is not present or invalid
     */
    public function validate() {
        $validKey = $this->getValidKey();
        if (isset($_POST[$this->keyInputName]) && $_POST[$this->keyInputName] === $validKey) {
            unset($_SESSION[$this->timestampSessionName]); // delete this if validation passes
            return TRUE;
        } else {
            throw new Exception("Please enable JavaScript to submit this form");
        }
        return FALSE;
    }

}

?>