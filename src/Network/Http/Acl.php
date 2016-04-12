<?php
/**
 * Created by IntelliJ IDEA.
 * User: nuomi
 * Date: 16/4/5
 * Time: 上午11:19
 */

namespace Zan\Framework\Network\Http;

use Zan\Framework\Foundation\Core\Config;
use Zan\Framework\Network\Http\Request\Request;
use Zan\Framework\Network\Http\Response\RedirectResponse;
use Zan\Framework\Network\Common\Client;
use Zan\Framework\Utilities\Types\Arr;

class Acl
{
    private $configKey = 'acl';

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->config = Config::get($this->configKey, null);
    }

    public function auth()
    {
        $aclWhiteList = $this->config['white_list'];
        $currentPath = $this->request->getPath();
        if (in_array($currentPath, $aclWhiteList)) {
            return;
        }
        $cookie = (yield getCookieHandler());
        $sid = (yield $cookie->get('sid', ''));
        $userId = (yield $cookie->get('user_id', 0));
        if ('' === $sid) {
            $cookie->set('redirect', $this->request->getFullUrl(), 0);
            yield RedirectResponse::create($this->config['login_url'], 302);
            return;
        } else {
            if (0 === $userId) {
                yield $this->setAdminInfoToCookie($sid);
            }
        }

        yield null;
    }

    private function setAdminInfoToCookie($sid)
    {
        if (!$sid) {
            return;
        }
        $cookie = (yield getCookieHandler());
        $userId = (yield $this->getAdminIdBySid($sid));
        yield $cookie->set('user_id', $userId, 0);

        $adminInfo = (yield $this->getAdminInfoById($userId));
        if (isset($adminInfo['account'])) {
            yield $cookie->set('account', $adminInfo['account'], 0);
        }
        if (isset($adminInfo['avatar'])) {
            yield $cookie->set('avatar', $adminInfo['avatar'], 0);
        }
        if (isset($adminInfo['nickname'])) {
            yield $cookie->set('nickname', $adminInfo['nick_name'], 0);
        }
    }

    private function getAdminIdBySid($sid)
    {
        $resp = (yield Client::call('account.sso.getAdminIdBySid', ['sid' => $sid]));
        $result = $resp['code'] === 0 ? $resp['data'] : null;
        yield $result;
    }

    private function getAdminInfoById($id)
    {
        $resp = (yield Client::call('account.admin.getPersonalInfo', ['admin_id' => $id]));
        $result = $resp['code'] === 0 ? $resp['data'] : null;
        yield $result;
    }

}