<?php

namespace Spothit\Webhook;

use Symfony\Component\Webhook\Client\AbstractRequestParser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher\MethodRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\RemoteEvent\Event\Sms\SmsEvent;
use Symfony\Component\Webhook\Exception\RejectWebhookException;

final class RequestParser extends AbstractRequestParser
{

    protected function getRequestMatcher(): RequestMatcherInterface
    {
        return new MethodRequestMatcher('POST');
    }

    protected function doParse(Request $request, #[\SensitiveParameter] string $secret): ?SmsEvent
    {
        $spothit = $request->request->all();
        if (
            !isset($spothit['MessageStatus'])
            || !isset($spothit['MessageSid'])
            || !isset($spothit['To'])
        ) {
            throw new RejectWebhookException(406, 'Spothit est incorrect.');
        }

        $name = match ($spothit['MessageStatus']) {
            0 => null,                      // En attente
            1 => SmsEvent::DELIVERED,       // Livré
            2 => null,                      // Envoyé
            3 => null,                      // En cours
            4 => SmsEvent::FAILED,          // Echec
            5 => SmsEvent::FAILED,          // Expiré
            default => throw new RejectWebhookException(406, sprintf('Évènement non supporté "%s".', $spothit['event'])),
        };
        if (!$name) {
            return null;
        }

        $event = new SmsEvent($name, $spothit['MessageSid'], $spothit);
        $event->setRecipientPhone($spothit['To']);

        return $event;
    }
}