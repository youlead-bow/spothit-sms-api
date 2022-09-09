<?php 

namespace Spothit;

use DateTimeInterface;
use Spothit\Exception\RequestException;
use Spothit\Exception\ResponseException;
use Spothit\Exception\ResponseCodeException;

class Base {

    const BASE_URL = 'https://www.spot-hit.fr';

    /**
     * API key available on your manager.
     *
     * @var string
     */
    public string $apiKey;

    /**
     * Numbers in international format + XXZZZZZ.
     *
     * @var array
     */
    public array $smsRecipients = [];

    /**
     * @var DateTimeInterface
     */
    public DateTimeInterface $sendingTime;

    /**
     * Sender of the message (if the user allows it), 3-11 alphanumeric characters (a-zA-Z).
     *
     * @var string
     */
    public string $smsSender = 'Spot-Hit';

    /**
     * Campaign identifier used for Spot-Hit administration panel and not visible to the recipients.
     *
     * @var ?string
     */
    public ?string $campaignName = null;

    /**
     * Allow long SMS
     *
     * @var bool
     */
    public int|bool $allowLongSms = 1;

    /**
     * callback URL
     *
     * @var string
     */
    public string $callbackUrl;


    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
        $this->sendingTime = new \DateTime();
    }

    public function setSmsRecipients(array $smsRecipients): void
    {
        $this->smsRecipients = $smsRecipients;
    }

    public function setSendingTime(\DateTime $sendingTime): void
    {
        $this->sendingTime = $sendingTime;
    }

    public function setSmsSender($smsSender): void
    {
        $this->smsSender = $smsSender;
    }

    public function setCampaignName($campaignName): void
    {
        $this->campaignName = $campaignName;
    }

    public function setCallbackUrl($url): void
    {
        $this->callbackUrl = $url;
    }

    public function setAllowLongSms(bool|int $allowLongSms): void
    {
        $this->allowLongSms = $allowLongSms;
    }

    public function httpRequest($path, array $fields)
    {
        set_time_limit(0);

        $qs = [];
        foreach ($fields as $k => $v) {
            $qs[] = $k . '=' . urlencode($v);
        }

        $request = implode('&', $qs);

        if (false === $ch = curl_init(self::BASE_URL . $path)) {
            throw new RequestException(sprintf('Request initialization to "%s" failed.', $path));
        }

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (false === $result = curl_exec($ch)) {
            curl_close($ch);

            throw new ResponseException(sprintf(
                'Failed to get response from "%s". Response: %s.',
                $path,
                $result
            ));
        }

        if (200 !== $code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
            throw new ResponseException(sprintf(
                'Server returned "%s" status code. Response: %s.',
                $code,
                $result
            ));
        }

        curl_close($ch);

        $responseArray = json_decode($result, true);

        if ($responseArray['resultat'] != 1) {
            throw new ResponseCodeException(sprintf(
                'Server returned "%s" error code.',
                $responseArray['erreurs']
            ), $responseArray['erreurs']);
        }

        return $responseArray;
    }

    /**
     * Returns credit balance as a number of Euros left on account.
     *
     * @return array
     *
     * @see https://www.spot-hit.fr/api/credits
     */
    public function getCredit(): array
    {
        $data = [
            'key' => $this->apiKey,
        ];

        return $this->httpRequest('/api/credits', $data);
    }

}