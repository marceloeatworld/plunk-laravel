<?php

namespace MarceloEatWorld\PlunkLaravel\Transport;

use MarceloEatWorld\PlunkLaravel\Services\PlunkService;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;

class PlunkTransport extends AbstractTransport
{
    public function __construct(
        protected readonly PlunkService $plunkService,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $recipients = $this->collectRecipients($email);
        $options = $this->buildOptions($email);

        $body = $email->getHtmlBody() ?? $email->getTextBody() ?? '';
        if (is_resource($body)) {
            $body = stream_get_contents($body) ?: '';
        }

        $result = $this->plunkService->sendEmail(
            $recipients,
            $email->getSubject() ?? '',
            (string) $body,
            $options,
        );

        if (isset($result['success']) && $result['success'] === false) {
            throw new \RuntimeException(
                'Plunk: ' . ($result['error'] ?? 'Unknown error')
            );
        }
    }

    /**
     * Collect all recipients (To + CC + BCC) since Plunk only has a "to" field.
     */
    protected function collectRecipients($email): string|array
    {
        $recipients = [];

        foreach ($email->getTo() as $address) {
            $recipients[] = $this->formatAddress($address);
        }

        foreach ($email->getCc() as $address) {
            $recipients[] = $this->formatAddress($address);
        }

        foreach ($email->getBcc() as $address) {
            $recipients[] = $this->formatAddress($address);
        }

        return count($recipients) === 1 ? $recipients[0] : $recipients;
    }

    /**
     * Format an address as string or {name, email} object for the Plunk API.
     */
    protected function formatAddress(Address $address): string|array
    {
        if ($address->getName() !== '') {
            return [
                'name' => $address->getName(),
                'email' => $address->getAddress(),
            ];
        }

        return $address->getAddress();
    }

    protected function buildOptions($email): array
    {
        $options = [];

        // From
        $fromAddresses = $email->getFrom();
        if (!empty($fromAddresses)) {
            $from = $fromAddresses[0];
            $options['from'] = $from->getAddress();
            if ($from->getName() !== '') {
                $options['name'] = $from->getName();
            }
        }

        // Reply-To
        $replyTo = $email->getReplyTo();
        if (!empty($replyTo)) {
            $options['reply'] = $replyTo[0]->getAddress();
        }

        // Custom headers (exclude standard ones handled by Plunk)
        $excludedHeaders = [
            'from', 'to', 'cc', 'bcc', 'subject', 'reply-to',
            'content-type', 'content-transfer-encoding', 'mime-version',
            'message-id', 'date',
        ];
        $headers = [];
        foreach ($email->getHeaders()->all() as $header) {
            if (!in_array(strtolower($header->getName()), $excludedHeaders)) {
                $headers[$header->getName()] = $header->getBodyAsString();
            }
        }
        if (!empty($headers)) {
            $options['headers'] = $headers;
        }

        // Attachments (base64 encoded for Plunk API)
        $attachments = $email->getAttachments();
        if (!empty($attachments)) {
            $options['attachments'] = array_map(fn ($part) => [
                'filename' => $part->getFilename() ?? 'attachment',
                'content' => base64_encode($part->getBody()),
                'contentType' => $part->getMediaType() . '/' . $part->getMediaSubtype(),
            ], $attachments);
        }

        return $options;
    }

    public function __toString(): string
    {
        return 'plunk';
    }
}
