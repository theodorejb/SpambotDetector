<?php

/**
 * BotDetector prevents spam form submissions by requiring a unique token, fetched 
 * with Ajax, to be submitted via a hidden field (requires JavaScript to be enabled)
 * 
 * See README for usage instructions and more information about how the library works.
 *
 * Website: https://github.com/theodorejb/SpambotDetector
 * Updated: 2013-08-04
 *
 * @author Theodore Brown
 * @version 1.0.2
 */
class SpambotDetect {

    private $loadTimestamp, $secretKey;
    private $timestampSessionName = "SpambotDetectTime";

    const secretKeySessionName = "SpambotDetectSecret";

    /**
     * @param string $secret A secret string of your choosing which will be salted/hashed to create a valid token
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
        $tokenFieldName = $this->getTokenFieldName();

        echo <<<_SCRIPT
<script>
    (function() {
        var url = "$pathToAjaxResponseFile";
        var method = "GET";
        var async = true;
        var formId = "$formId";
        var tokenFieldName = "$tokenFieldName";

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
                            var tokenInputElement = document.createElement('input');
                            tokenInputElement.setAttribute('type', 'hidden');
                            tokenInputElement.setAttribute('name', tokenFieldName);
                            tokenInputElement.setAttribute('value', this.responseText);
                            form.appendChild(tokenInputElement);
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
        $TFName = $this->getTokenFieldName();

        if (isset($_GET[$TFName])) {
            $token = $_GET[$TFName];
        } elseif (isset($_POST[$TFName])) {
            $token = $_POST[$TFName];
        }

        if (isset($token) && $token === $validKey) {
            // the token is valid!
            // unset session values and return true
            unset($_SESSION[$this->timestampSessionName]);
            unset($_SESSION[SpambotDetect::secretKeySessionName]);
            return TRUE;
        } else {
            throw new Exception("Please enable JavaScript to submit this form");
        }
        return FALSE;
    }

    /**
     * To make things harder for bots, the input field name will be randomized by 
     * setting it to a hash of the secret key appended to itself + the load timestamp.
     */
    private function getTokenFieldName() {
        $secret = $this->secretKey . $this->secretKey;
        return md5($secret . $this->loadTimestamp);
    }

}

?>