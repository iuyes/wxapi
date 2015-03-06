<?php
namespace Xueba\WxApi\QY;
use GuzzleHttp\Event\CompleteEvent;
use Xueba\WxApi\Crypt;
use Xueba\WxApi\Exception;

class Client extends \GuzzleHttp\Client
{
    use Crypt;

    const BASE_URL = 'https://qyapi.weixin.qq.com';
    const GET_ACCESS_TOKEN_URI = 'cgi-bin/gettoken';
    const SEND_MESSAGE_URI = 'cgi-bin/message/send';

    private $_corpId;
    private $_corpSecret;

    public function __construct($corpId, $corpSecret, $token = null, $encodingAesKey = null, array $config = [])
    {
        parent::__construct(array_merge($config, ['base_url' => self::BASE_URL]));
        $this->_corpId = $corpId;
        $this->_corpSecret = $corpSecret;
        $this->_token = $token;
        $this->_encodingAesKey = $encodingAesKey;

        if (!is_null($token) && !is_null($encodingAesKey))
        {
            self::$_wxcpt = new \WXBizMsgCrypt($this->_token, $this->_encodingAesKey, $this->_corpId);
        }

        $this->getEmitter()->on('complete', function (CompleteEvent $e) {
            $result = $e->getResponse()->json();
            if (isset($result['errcode']) && $result['errcode'] != 0)
            {
                throw new Exception($result['errmsg'], $result['errcode']);
            }
        });
    }

    public function getAccessToken()
    {
        $response = $this->get(self::GET_ACCESS_TOKEN_URI, [
            'query' => ['corpid' => $this->_corpId, 'corpsecret' => $this->_corpSecret]
        ]);
        return $response->json()['access_token'];
    }

    public function sendMessage($jsonMessage)
    {
        $response = $this->post(self::SEND_MESSAGE_URI, [
            'query' => ['access_token' => $this->getAccessToken()],
            'body'  => $jsonMessage,
        ]);

        return $response->json();
    }
}
