<?php

namespace Panelplace\FacebookAuthPassport;

use RuntimeException;
use Illuminate\Http\Request;
use Laravel\Passport\Bridge\User;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\RequestEvent;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;

use League\OAuth2\Server\Grant\AbstractGrant;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use App\Http\Controllers\API\v2\MemberController;

class FacebookLoginRequestGrant extends AbstractGrant
{
    /**
     * @param UserRepositoryInterface         $userRepository
     * @param RefreshTokenRepositoryInterface $refreshTokenRepository
     */
    public function __construct(
        UserRepositoryInterface $userRepository,
        RefreshTokenRepositoryInterface $refreshTokenRepository
    )
    {
        $this->setUserRepository($userRepository);
        $this->setRefreshTokenRepository($refreshTokenRepository);

        $this->refreshTokenTTL = new \DateInterval('P1M');
    }

    /**
     * {@inheritdoc}
     */
public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        \DateInterval $accessTokenTTL
    )
    {
        // Validate request
        $client = $this->validateClient($request);
        $scopes = $this->validateScopes($this->getRequestParameter('scope', $request));
        $user = $this->validateUser($request);

        // Finalize the requested scopes
        $scopes = $this->scopeRepository->finalizeScopes($scopes, $this->getIdentifier(), $client, $user->getIdentifier());

        // Issue and persist new tokens
        $accessToken = $this->issueAccessToken($accessTokenTTL, $client, $user->getIdentifier(), $scopes);
        $refreshToken = $this->issueRefreshToken($accessToken);

        // Inject tokens into response
        $responseType->setAccessToken($accessToken);
        $responseType->setRefreshToken($refreshToken);

        return $responseType;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return 'facebook_login';
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return UserEntityInterface
     * @throws OAuthServerException
     */
    protected function validateUser(ServerRequestInterface $request)
    {
        $laravelRequest = new Request($request->getParsedBody());

        $user = $this->getEntityBySocialCredentials($laravelRequest);

        if ($user instanceof UserEntityInterface === false) {
            $this->getEmitter()->emit(new RequestEvent(RequestEvent::USER_AUTHENTICATION_FAILED, $request));

            throw OAuthServerException::invalidCredentials();
        }

        return $user;
    }

    /**
     * Retrieve user by request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Laravel\Passport\Bridge\User|null
     * @throws \League\OAuth2\Server\Exception\OAuthServerException
     */

    public function getEntityBySocialCredentials($data)
    {
        $provider = config('auth.guards.api.provider');

        if (is_null($model = config('auth.providers.'.$provider.'.model'))) {
            throw new RuntimeException('Unable to determine authentication model from configuration.');
        }

        $result = (new MemberController)->fbValidation($data);

        if (!empty($result['code'])) {
            $code = $result['code'];
            if ($code === 205 ) {
                throw new RuntimeException('parameter Device Type is invalid');
            }
            elseif($code === 202) {
                 throw new RuntimeException('parameter Email is invalid');
            }
            elseif($code === 117) {
                 throw new RuntimeException('parameter Imei is Required');
            }
            elseif($code === 115) {
                 throw new RuntimeException('parameter OS Version is Required');
            }
            elseif($code === 114) {
                 throw new RuntimeException('parameter Device Type is Required');
            }
            elseif($code === 116) {
                 throw new RuntimeException('parameter App Version is Required');
            }
            elseif($code === 103) {
                 throw new RuntimeException('parameter Email is Required');
            }
        }
        else {
            $checkuser = (new $model)->where('email', $data['username'])->first();

            if (! $checkuser) {
                        $result = (new MemberController)->fblogin($data);
            } 
            
            if (method_exists($model, 'findForPassport')) {
                $user = (new $model)->findForPassport($data['username']);
            } else {
                $user = (new $model)->where('email', $data['username'])->first();
                //echo $user;die();
                $result = (new MemberController)->fbloginupdate($data, $user);
            }
            
           return new User($user->getAuthIdentifier());    
        }

    }
}
