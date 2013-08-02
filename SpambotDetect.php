<?php

/**
 * BotDetector prevents spam form submissions by requiring a unique token, fetched 
 * with Ajax, to be submitted via a hidden field (requires JavaScript to be enabled)
 * 
 * See README for usage instructions and more information about how the library works.
 *
 * Website: https://github.com/theodorejb/SpambotDetector
 * Updated: 2013-08-02
 *
 * @author Theodore Brown
 * @version 1.0.1
 */
class SpambotDetect {

    private $keyInputName = "SpambotDetectKey";
    private $timestampSessionName = "SpambotDetectTime";
    const secretKeySessionName = "SpambotDetectSecret";
    private $loadTimestamp, $secretKey;

    /**
     * @param string $secret A secret string of your choosing which will be used as the base for a salted hash key
     * @throws Exception if a session doesn't exist and can't be started
     */
    public function __construct($secret) {
        $this->secretKey = $secret;

        // make sure a session has been started
        if (session_id() == '') {
            // no session has been started; try starting it
            if (!session_start())
                throw new Exception("Unable to start session");
            else
                session_regenerate_id();
        }
        
        // store the secret key in a session variable so that it can be reused
        // on the Ajax response page to generate and return a valid key
        $_SESSION[SpambotDetect::secretKeySessionName] = $this->secretKey;

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
     * @param String $pathToAjaxResponseFile A relative path from the form page to SpambotAjax.php
     */
    public function insertToken($formId, $pathToAjaxResponseFile) {

        echo <<<_SCRIPT
<script>
    (function() {
        var url = "$pathToAjaxResponseFile";
        var method = "GET";
        var async = true;
        var formId = "$formId";
        var keyInputName = "$this->keyInputName";

        try {
            // test for modern browsers first
            var request = new XMLHttpRequest();
        } catch (e1) {
            try {
                // IE 6?
                request = new ActiveXObject("Msxml2.XMLHTTP");
            } catch (e2) {
                try {
                    // IE 5?
                    request = new ActiveXObject("Microsoft.XMLHTTP");
                } catch (e3) {
                    // There is no Ajax support
                    request = false;
                }
            }
        }

        if (request !== false) {
            // the browser supports Ajax
            request.open(method, url, async);

            request.onreadystatechange = function() {
                if (this.readyState === 4) {
                    if (this.status === 200) {
                        if (this.responseText !== null) {
                            // request was successful; insert the response into the form
                            var form = document.getElementById(formId);
                            var keyInputElement = document.createElement('input');
                            keyInputElement.setAttribute('type', 'hidden');
                            keyInputElement.setAttribute('name', keyInputName);
                            keyInputElement.setAttribute('value', this.responseText);
                            form.appendChild(keyInputElement);
                        } else {
                            console.log("No Ajax data received");
                        }
                    } else {
                        console.log("Ajax error: " + this.statusText);
                    }
                }
            };

            request.send(null);

        } else {
            alert("It looks like your browser doesn't support Ajax. Please upgrade to a modern version.");
        }
    })();
</script>
_SCRIPT;
    }

    /**
     * Generates a valid key to include in a hidden form field via JavaScript
     * The key is created by salting the secret key with the current timestamp
     */
    public function getValidKey() {
        $secret = $this->secretKey;
        $salt = $this->loadTimestamp;
        return sha1($secret . $salt);
    }

    /**
     * Checks for a POSTed token matching the hashed/salted key
     * 
     * @return boolean TRUE if valid, otherwise FALSE
     * @throws Exception if the token is not present or invalid
     */
    public function validate() {
        $validKey = $this->getValidKey();
        if (isset($_REQUEST[$this->keyInputName]) && $_REQUEST[$this->keyInputName] === $validKey) {
            unset($_SESSION[$this->timestampSessionName]); // delete this if validation passes
            return TRUE;
        } else {
            throw new Exception("Please enable JavaScript to submit this form");
        }
        return FALSE;
    }

}

?>