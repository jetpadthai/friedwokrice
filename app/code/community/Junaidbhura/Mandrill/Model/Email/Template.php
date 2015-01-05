<?php
/**
 * Email / Template Model
 *
 * @category    Model
 * @package     Junaidbhura_Mandrill
 * @author      Junaid Bhura <info@junaidbhura.com>
 */

class Junaidbhura_Mandrill_Model_Email_Template extends Mage_Core_Model_Email_Template {
	/* Variables */
	private $_bcc_array = array();

	/**
	 * Override parent's addBcc() function
	 *
	 * @param string|array     The BCC email(s)
	 * @return object     Current object
	 */
	public function addBcc( $bcc ) {
		// Check if extension is enabled and API key is entered
		if ( Mage::getStoreConfig( 'mandrill/mandrill/active' ) && Mage::getStoreConfig( 'mandrill/mandrill/api_key' ) != '' ) {
			
			if ( is_array( $bcc ) ) {
				foreach ( $bcc as $email ) {
					$this->_bcc_array[] = $email;
				}
			}
			elseif ( $bcc ) {
				$this->_bcc_array[] = $bcc;
			}
			return $this;

		}
		else {
			// Extension is not enabled, use parent's function
			return parent::addBcc( $bcc );
		}

	}

	/**
	 * Override parent's send() function
	 *
	 * @param array|string $email     E-mail(s)
	 * @param array|string|null $name      receiver name(s)
	 * @param array   $variables template variables
	 * @return  boolean
	 * */
	public function send( $email, $name = null, array $variables = array() ) {
		// Check if extension is enabled and API key is entered
		if ( Mage::getStoreConfig( 'mandrill/mandrill/active' ) && Mage::getStoreConfig( 'mandrill/mandrill/api_key' ) != '' ) {

			if ( ! $this->isValidForSend() ) {
				Mage::logException( new Exception( 'This letter cannot be sent.' ) );
				return false;
			}

			// Set up names and email addresses
			$emails = array_values( (array)$email );
			$names = is_array( $name ) ? $name : (array)$name;
			$names = array_values( $names );
			foreach ( $emails as $key => $email ) {
				if ( ! isset( $names[$key] ) ) {
					$names[ $key ] = substr( $email, 0, strpos( $email, '@' ) );
				}
			}

			// Get message
			$this->setUseAbsoluteLinks( true );
			$variables['email'] = reset( $emails );
			$variables['name'] = reset( $names );
			$message = $this->getProcessedTemplate( $variables, true );
			
			// Initialize Mandrill
			$mandrill = new Junaidbhura_Mandrill( Mage::getStoreConfig( 'mandrill/mandrill/api_key' ) );

			// Prepare email
			$email = array( 'subject' => $this->getProcessedTemplateSubject( $variables ), 'to' => array() );

			for ( $i = 0; $i < count( $emails ); $i++ ) {
				if ( isset( $names[ $i ] ) ) {
					$email['to'][] = array(
						'email' => $emails[ $i ],
						'name' => $names[ $i ]
					);
				}
				else {
					$email['to'][] = array(
						'email' => $emails[ $i ],
						'name' => ''
					);
				}
			}

			for ( $i = 0; $i < count( $this->_bcc_array ); $i++ ) {
				$email['to'][] = array(
					'email' => $this->_bcc_array[ $i ],
					'name' => ''
				);
			}

			if ( Mage::getStoreConfig( 'mandrill/mandrill/from_name' ) != '' )
				$email['from_name'] = Mage::getStoreConfig( 'mandrill/mandrill/from_name' );
			else
				$email['from_name'] = $this->getSenderName();

			if ( Mage::getStoreConfig( 'mandrill/mandrill/from_email' ) != '' )
				$email['from_email'] = Mage::getStoreConfig( 'mandrill/mandrill/from_email' );
			else
				$email['from_email'] = $this->getSenderEmail();

			if( $this->isPlain() )
				$email['text'] = $message;
			else
				$email['html'] = $message;

			// Send the email!
			try {
				$result = $mandrill->messages->send( $email );
			}
			catch( Exception $e ) {
				// Oops, some error in sending the email!
				Mage::logException( $e );
				return false;
			}

			// Woo hoo! Email sent!
			return true;

		}
		else {
			// Extension is not enabled, use parent's function
			return parent::send( $email, $name, $variables );
		}
	}
}
