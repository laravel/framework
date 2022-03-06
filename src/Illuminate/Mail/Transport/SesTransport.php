<?php

namespace Illuminate\Mail\Transport;

use Aws\Exception\AwsException;
use Aws\Ses\SesClient;
use Swift_Mime_SimpleMessage;
use Swift_TransportException;

class SesTransport extends Transport
{
    /**
     * Amazon SES has a limit on the total number of recipients per message.
     * See Also: https://docs.aws.amazon.com/ses/latest/dg/quotas.html.
     */
    protected const RECIPIENT_LIMIT = 50;

    /**
     * The Amazon SES instance.
     *
     * @var \Aws\Ses\SesClient
     */
    protected $ses;

    /**
     * The Amazon SES transmission options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Create a new SES transport instance.
     *
     * @param  \Aws\Ses\SesClient  $ses
     * @param  array  $options
     * @return void
     */
    public function __construct(SesClient $ses, $options = [])
    {
        $this->ses = $ses;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     *
     * @return int
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        try {
            /**
             * Batch up API calls to adhere to the recipient limit of the service.
             * See Also: https://docs.aws.amazon.com/aws-sdk-php/v2/api/class-Aws.Ses.SesClient.html#_sendRawEmail.
             */
            foreach (collect(array_merge($message->getTo(), $message->getCc(), $message->getBcc()))->chunk(self::RECIPIENT_LIMIT) as $recipients) {
                $result = $this->ses->sendRawEmail(
                    array_merge(
                        $this->options, [
                            'Source' => key($message->getSender() ?: $message->getFrom()),
                            'RawMessage' => [
                                'Data' => $message->toString(),
                            ],
                            'Destinations' => $recipients,
                        ]
                    )
                );

                $messageId = $result->get('MessageId');

                $message->getHeaders()->addTextHeader('X-Message-ID', $messageId);
                $message->getHeaders()->addTextHeader('X-SES-Message-ID', $messageId);
            }
        } catch (AwsException $e) {
            throw new Swift_TransportException('Request to AWS SES API failed.', $e->getCode(), $e);
        }

        $this->sendPerformed($message);

        return $this->numberOfRecipients($message);
    }

    /**
     * Get the Amazon SES client for the SesTransport instance.
     *
     * @return \Aws\Ses\SesClient
     */
    public function ses()
    {
        return $this->ses;
    }

    /**
     * Get the transmission options being used by the transport.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set the transmission options being used by the transport.
     *
     * @param  array  $options
     * @return array
     */
    public function setOptions(array $options)
    {
        return $this->options = $options;
    }
}
