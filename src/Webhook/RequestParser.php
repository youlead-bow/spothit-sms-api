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
        return new MethodRequestMatcher('GET');
    }

    protected function doParse(Request $request, #[\SensitiveParameter] string $secret): ?SmsEvent
    {
        $spothit = $request->query->all();
        if (
            !isset($spothit['statut'])
            || !isset($spothit['id_message'])
            || !isset($spothit['numero'])
        ) {
            throw new RejectWebhookException(406, 'Spothit est incorrect.');
        }

        $name = match ($spothit['statut']) {
            '0' => null,                      // En attente
            '1' => SmsEvent::DELIVERED,       // Livré
            '2' => null,                      // Envoyé
            '3' => null,                      // En cours
            '4' => SmsEvent::FAILED,          // Echec
            '5' => SmsEvent::FAILED,          // Expiré
            default => throw new RejectWebhookException(406, sprintf('Évènement non supporté "%s".', $spothit['statut'])),
        };
        if (!$name) {
            return null;
        }

        $event = new SmsEvent($name, $spothit['id_message'], $spothit);
        $event->setRecipientPhone($spothit['numero']);

        return $event;
    }
}