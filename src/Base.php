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
     * @var ?DateTimeInterface
     */
    public ?DateTimeInterface $sendingTime = null;

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
     * @var ?string
     */
    public ?string $callbackUrl = null;

    private array $fields = [];

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->sendingTime = new \DateTime();
    }

    public function setSmsRecipients(array $smsRecipients): self
    {
        $this->smsRecipients = $smsRecipients;

        return $this;
    }

    public function setSendingTime(\DateTime $sendingTime): self
    {
        $this->sendingTime = $sendingTime;

        return $this;
    }

    public function setSmsSender(string $smsSender): self
    {
        $this->smsSender = $smsSender;

        return $this;
    }

    public function setCampaignName(string $campaignName): self
    {
        $this->campaignName = $campaignName;

        return $this;
    }

    public function setCallbackUrl(string $url): self
    {
        $this->callbackUrl = $url;

        return $this;
    }

    public function setAllowLongSms(bool|int $allowLongSms): self
    {
        $this->allowLongSms = $allowLongSms;

        return $this;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function httpRequest(string $path, array $fields): array
    {
        $this->fields = $fields;
        set_time_limit(0);

        $request = http_build_query($fields, '', '&');

        if (false === $ch = curl_init(self::BASE_URL . $path)) {
            throw new RequestException(sprintf('Request initialization to "%s" failed.', $path));
        }

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);

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