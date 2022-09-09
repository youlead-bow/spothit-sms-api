<?php

namespace Spothit\Client;

use DateTime;
use Spothit\Base;

/**
 * Spothit API client.
 */
class Sms extends Base
{

    /**
     * Sends a simple SMS.
     *
     * @param string $smsText Message text (maximum 459 characters).
     *
     * @return array
     *
     * @see https://www.spot-hit.fr/documentation-api#chapter2para1
     */
    public function send(string $smsText): array
    {
        $data = [
            'key' => $this->apiKey,
            'message' => $smsText,
            'destinataires' => implode(',', $this->smsRecipients),
            'expediteur' => $this->smsSender,
            'smslong' => $this->allowLongSms
        ];

        if (!empty($this->campaignName)) {
            $data['nom'] = $this->campaignName;
        }

        if ($this->sendingTime > (new DateTime())) {
            $data['date'] = $this->sendingTime->getTimestamp();
        }

        if ($this->callbackUrl) {
            $data['url'] = $this->callbackUrl;
        }

        return $this->httpRequest('/api/envoyer/sms', $data);
    }
}
