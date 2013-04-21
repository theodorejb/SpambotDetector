<?php

/**
 * BotDetector prevents spam form submissions by requiring a unique token, fetched with Ajax, to be submitted via a hidden field (requires JavaScript to be enabled)
 * 
 * Currently depends on jQuery, and only supports forms submitted via POST
 * 
 * Usage:
 * 
 * 1. Change the $secretKey property to a secret string of your own choosing (preferably something random that won't be easily guessed)
 * 2. Include BotDetector.php on the page you want to protect and initialize the BotDetector class
 * 3. After your form, call the insertToken() method and pass it the form ID and relative path to the BotDetector folder
 * 4. When the form is submitted, call the validate() method within a try...catch block
 * 
 * How it works:
 * 
 * 1. When the form is requested, the current timestamp is stored a session variable.
 * 2. When the key is requested via Ajax, BotDetector returns the timestamp, salted & hashed (difficult to fake)
 * 3. JavaScript inserts the key into a hidden input field in the form
 * 4. When the form is submitted, if the key is not present or does not match the timestamp in the session, validation will fail.
 *
 * @author Theodore Brown
 * @version 2013.03.25
 */
class BotDetector {

    private $keyInputName = "BotDetectorKey";
    private $timestampSessionName = "BotDetectorTime";
    private $loadTimestamp;
    private $secretKey = 'Change this to a secret string of your choosing';

    public function __construct() {

        if (session_id() == '') {
            // no session has been started; try starting it
            if (!session_start())
                throw new Exception("Unable to start session");
            else
                session_regenerate_id(TRUE);
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
     * @param String $pathToBotDetector A relative path from the form page to the BotDetector folder
     */
    public function insertToken($formId, $pathToBotDetector) {
        echo <<<_SCRIPT
        <script>
            $.ajax({
                url: "$pathToBotDetector/BotDetectorAjax.php",
                type: "POST",
                dataType: "text",
                success: function(data) {
                    var form = document.getElementById('$formId');
                    var keyInputElement = document.createElement('input');
                    keyInputElement.setAttribute('type', 'hidden');
                    keyInputElement.setAttribute('name', '$this->keyInputName');
                    keyInputElement.setAttribute('value', data);
                    $(form).append(keyInputElement);
                }
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