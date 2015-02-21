<?php
require 'vendor/autoload.php';
class Mailer {

	// SMTP transport
	const SMTP=0;
	// Sendmail transport
	const SENDMAIL=1;
	// PHP mail transport
	const MAIL=2;

	const SSL='ssl';
	const TLS='tls';
	
	protected $transport;
	protected $dump;

	/**
	 * Initialize the transport mechanism. Default is PHP mail.
	 * @param int 	transportType	one of SMTP, SENDMAIL or MAIL
	 * @param array config 			configuration values, mainly for the SMTP transport:
	 *								'smtp-server' - server name
	 *								'smtp-port' - port
	 *								'smtp-encryption' - 'ssl' or 'tls', if encryption is required
	 *								'username' - SMTP user
	 *								'password' - SMTP password
	 */
	function __construct($transportType=Mailer::MAIL, $config=array()) {
		if ($transportType == Mailer::SMTP) {
			// Create the Transport
			if (isset($config['smtp-encryption']))
				$this->transport = Swift_SmtpTransport::newInstance($config['smtp-server'], $config['smtp-port'], $config['smtp-encryption']);
			else
				$this->transport = Swift_SmtpTransport::newInstance($config['smtp-server'], $config['smtp-port']);
		  	$this->transport->setUsername($config['username'])
		 					->setPassword($config['password']);

		} elseif ($transportType == Mailer::SENDMAIL) {
			$this->transport = Swift_SendmailTransport::newInstance('/usr/sbin/sendmail -bs');
		} else {
			$this->transport = Swift_MailTransport::newInstance(); 
		}
	}

	/**
	 * Create a mail for sending a book to a Kindle account or elsewhere.
	 * @param string 	bookpath	complete path to the book/file to be sent
	 * @param string 	subject 	mail subject line
	 * @param string 	recipient 	mail address of recipient
	 * @param string 	sender 		mail address of sender
	 * @param string 	filename 	new filename
	 * @return 						email
	 */
	public function createBookMessage($bookpath, $subject, $recipient, $sender,$filename) {
		// Create the message
		$message = Swift_Message::newInstance()
			// Give the message a subject
			->setSubject($subject)
			// Set the From address with an associative array
			->setFrom(array($sender))
			// Set the To addresses with an associative array
			->setTo(array($recipient))
			// Give it a body
			->setBody('This book was sent to you by BicBucStriim.')
			// Optionally add any attachments
			->attach(Swift_Attachment::fromPath($bookpath)->setFilename($filename));
		return $message;
	}

	/**
	* Returns a dump of the last sending process. Just for troubleshooting.
	*/
	public function getDump() {
		return $this->dump;
	}

	/**
	 * Send an email via the transport.
	 * @param Swift_Message	message 	email
	 * @return 							number of messages sent
	 */
	public function sendMessage($message) {
		$mailer = Swift_Mailer::newInstance($this->transport);
		$this->dump = '';
		$logger = new Swift_Plugins_Loggers_ArrayLogger();
		$mailer->registerPlugin(new Swift_Plugins_LoggerPlugin($logger));
		try {
			return $mailer->send($message);
		} catch (Excpetion $e) {
			$this->dump = $e->getMessage().' -- '.$logger->dump();
			return 0;
		}
	}	

}

?>
