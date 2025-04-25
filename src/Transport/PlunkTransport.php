<?php

namespace MarceloEatWorld\PlunkLaravel\Transport;

use MarceloEatWorld\PlunkLaravel\Services\PlunkService;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;

class PlunkTransport extends AbstractTransport
{
    /**
     * @var PlunkService
     */
    protected $plunkService;

    /**
     * Create a new Plunk Transport instance.
     *
     * @param PlunkService $plunkService
     * @return void
     */
    public function __construct(PlunkService $plunkService)
    {
        parent::__construct();
        $this->plunkService = $plunkService;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        
        $to = array_map(function (Address $address) {
            return $address->getAddress();
        }, $email->getTo());
        
        $to = count($to) === 1 ? $to[0] : $to;
        
        $fromAddresses = $email->getFrom();
        $from = null;
        $name = null;
        
        if (count($fromAddresses) > 0) {
            $fromAddress = $fromAddresses[0];
            $from = $fromAddress->getAddress();
            $name = $fromAddress->getName();
        }
        
        $replyToAddresses = $email->getReplyTo();
        $replyTo = null;
        
        if (count($replyToAddresses) > 0) {
            $replyToAddress = $replyToAddresses[0];
            $replyTo = $replyToAddress->getAddress();
        }
        
        $headers = [];
        foreach ($email->getHeaders()->all() as $header) {
            if (!in_array(strtolower($header->getName()), ['from', 'to', 'subject', 'cc', 'bcc'])) {
                $headers[$header->getName()] = $header->getBodyAsString();
            }
        }
        
        $options = [];
        if ($from) $options['from'] = $from;
        if ($name) $options['name'] = $name;
        if ($replyTo) $options['reply'] = $replyTo;
        if (!empty($headers)) $options['headers'] = $headers;
        
        $body = $email->getHtmlBody();
        if (empty($body)) {
            $body = $email->getTextBody();
        }
        
        $result = $this->plunkService->sendEmail(
            $to,
            $email->getSubject(),
            $body,
            $options
        );
        
        if (isset($result['success']) && $result['success'] === false) {
            throw new \RuntimeException('Error sending via Plunk: ' . ($result['error'] ?? 'Unknown error'));
        }
    }

    /**
     * Get the string representation of the transport.
     *
     * @return string
     */
    public function __toString(): string
    {
        return 'plunk';
    }
}