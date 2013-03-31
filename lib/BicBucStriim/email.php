<?php

/**
 * Class for sending email to kindle
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author Tomas Kopecny <tomas@kopecny.info>
 * @package daily-comics-to-kindle
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @origin https://github.com/Georgo/daily-comics-to-kindle
 */

class Email {
	/** Return value of email function */
	public $ret = null;

	/** Settings */
	/** @BEFORE-FIRST-START: Change sender email address. Don't forget to allow this address in you Amazon account */
	//private $sender = 'your-email@example.com';

	/** Email variables */ 
	private $mail_header = '';
	private $mail_body = '';
	private $timestamp = 0;
	
	/**
	 * Add line to body
	 *
	 * @param string $value Body line
	 * @return void
	 */
	private function _addBodyLine($value)
	{
		if ($value) {
			$this->mail_body .= $value;
		}
	}

	/**
	 * Add line to headers, parse key and value
	 *
	 * @param string $header Header key
	 * @param string $value Header value
	 * @return void
	 */
	private function _addHeaderLine($header, $value)
	{
		if ($value) {
			if (preg_match('#([\x80-\xFF]){1}#', $value)) {
				$charset = 'UTF-8';
				$imePrefs = array();
				if ($encoding == 'B') {
					$imePrefs['scheme'] = 'B';
				} else {
					$imePrefs['scheme'] = 'Q';
				}
				$imePrefs['input-charset']  = $charset;
				$imePrefs['output-charset'] = $charset;
				$imePrefs['line-length'] = 74;
				$imePrefs['line-break-chars'] = "\n"; //Specified in RFC2047

				$this->mail_header .= iconv_mime_encode($header, $value, $imePrefs) ."\n";
				return;
			}
		}
		$this->mail_header .= $header.': '.$value."\n";
	}

	/**
	 * Header validation
	 *
	 * @param string $hdr_value Header value
	 * @param string $charset Header charset
	 * @param string $encoding Encoding method (Q / B)
	 * @param string $hdr_name Header name
	 * @return string Validated header value
	 */
	private function encodeHeader($hdr_value, $charset = 'ISO-8859-1', $encoding = 'Q', $hdr_name='X')
	{
		if (preg_match('#([\x80-\xFF]){1}#', $hdr_value)) {
			$imePrefs = array();
			if ($encoding == 'B') {
				$imePrefs['scheme'] = 'B';
			} else {
				$imePrefs['scheme'] = 'Q';
			}
			$imePrefs['input-charset']  = $charset;
			$imePrefs['output-charset'] = $charset;
			$imePrefs['line-length'] = 74;
			$imePrefs['line-break-chars'] = ""; //Specified in RFC2047

			$hdr_value = iconv_mime_encode($hdr_name, $hdr_value, $imePrefs);
			$hdr_value = preg_replace("#^{$hdr_name}\:\ #", "", $hdr_value);
		}
		return $hdr_value;
	}

	/**
	 * Send email with filename as attachment
	 *
	 * @param string $filename Path to filename to attach
	 * @param string $subject Email subject
	 * @param string $to Comma separated recipients
	 */

	public function Email($filename, $subject, $to, $from_email) {
		if(!file_exists($filename)) {
			printf("Specified file %s not exists.\n", $filename);
			$this->ret = false;
			return;
		}
		$this->timestamp = time();

		$text = 'This is a document sent by BicBucStriim to your Kindle.';

		$this->_addHeaderLine('Date', date('r', $this->timestamp));
		$this->_addHeaderLine('From', $from_email);
		$this->_addHeaderLine('X-Mailer', 'BicBucStriim v1.0');
		$this->_addHeaderLine('X-Priority', '3');
		$this->_addHeaderLine('MIME-Version','1.0');

		$boundary = '_'.substr(md5('GergoJeKing'.time()), 0, 4).'_-------'.md5('x'.time());

		$this->_addHeaderLine('Content-Type', sprintf("multipart/mixed;\n\tboundary=\"%s\"", $boundary));
		$this->_addBodyLine("This is a multi-part message in MIME format.\n");
		$this->_addBodyLine(sprintf("--%s\nContent-Type: text/plain; charset=UTF-8; format=flowed\nContent-Transfer-Encoding: 8bit\n\n", $boundary));
		$this->_addBodyLine($text."\n\n");
		$this->_addBodyLine(sprintf("--%s\nContent-Type: %s\nContent-Transfer-Encoding: base64\nContent-Disposition: attachment; filename=\"%s\"\n\n", $boundary, 'application/zip', $this->encodeHeader(basename($filename), 'UTF-8')));
		$this->_addBodyLine(wordwrap(base64_encode(file_get_contents($filename)), 72, "\n", true)."\n");
		$this->_addBodyLine(sprintf("--%s--\n", $boundary));

		$this->mail_body = trim($this->mail_body);
		$this->mail_header = trim($this->mail_header)."\n";

		$this->ret = mail(
			$to, // Recipient
			$this->encodeHeader($subject, 'UTF-8', 'Q', 'Subject'), // Subject
			$this->mail_body, // Body
			$this->mail_header, // Headers
			sprintf('-f%s', $from_email) // Sendmail parametrers
		);
	}
}

?>
