Spambot Detector
================

Prevent form spam without a CAPTCHA with this easy-to-use PHP class. Spambot Detector works by generating a unique token which must be requested via Ajax and inserted into the form prior to submission. Many spambots attempt to post data directly to a page without requesting it first or executing JavaScript, and this class prevents this behavior.

Note: Spambot Detector requires users to have JavaScript enabled.

Dependencies
------------

* jQuery

Usage guide
-----------

1. Change the `$secretKey` property to a secret string of your own choosing (preferably something random that won't be easily guessed)

2. Import and initialize the BotDetector class

	```php
	require 'files/BotDetector/BotDetector.php';
	$botDetector = new BotDetector();
	```

3. Call the `insertToken()` method after the form you wish to protect

	```html
	<form id="myForm" method="post">
		<input type="text" name="username" />
		<input type="password" name="password" />
		<input type="submit" value="Submit" />
	</form>
	<?php $botDetector->insertToken('myForm', '/files/BotDetector') ?>
	```

	This method accepts the form ID and path to the BotDetector folder as parameters.

4. When the form is submitted, call the `validate()` method within a try...catch block

	```php
	try {
		$botDetector->validate();
		// code to run if the validation passes
	} catch (Exception $exc) {
		$errorMessage = $exc->getMessage();
	}
	```

That's it! Feel free to use this code or fork the repository to make changes.

Author
------

Theodore Brown
www.designedbytheo.com