<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace DoctrineModule\Authentication\Adapter;

use Doctrine\Common\Persistence\ObjectRepository;
use Zend\Authentication\Adapter\AdapterInterface;
use Zend\Authentication\Adapter\Exception;
use Zend\Authentication\Result as AuthenticationResult;

/**
 * Abstract authentication adapter that uses a Doctrine object for verification.
 *
 * @license MIT
 * @link    http://www.doctrine-project.org/
 * @since   0.2.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class DoctrineObjectRepository implements AdapterInterface
{
    /**
     * Doctrine ObjectRepository instance
     *
     * @var ObjectRepository
     */
    protected $objectRepository;

    /**
     * Doctrine object class that holds the identity.
     *
     * @var string
     */
    protected $identityClassName;

    /**
     * Identity property to check credential against.
     *
     * @var string
     */
    protected $identityProperty;

    /**
     * Credential property to check credential against.
     *
     * @var string
     */
    protected $credentialProperty;

    /**
     * User supplied identity.
     *
     * @var string
     */
    protected $identityValue;

    /**
     * User supplied credential.
     *
     * @var string
     */
    protected $credentialValue;

    /**
     * User supplied credential.
     *
     * @var mixed
     */
    protected $credentialCallable;

    /**
     * User supplied identity
     *
     * @var mixed
     */
    protected $identityCallable;

    /**
     * Contains the authentication results.
     *
     * @var array
     */
    protected $authenticationResultInfo = null;

    /**
     * Constructor
     *
     * @param ObjectRepository $objectRepository   Object repository where to look for identities
     * @param string           $identityClassName  Class that the retrieved identities must be instances of
     * @param string           $identityProperty   Property field used to search the repository with the given identity value
     * @param string           $credentialProperty Credential to check if the retrieved identity is valid
     */
    public function __construct(
        ObjectRepository $objectRepository,
        $identityClassName,
        $identityProperty = 'username',
        $credentialProperty = 'password'
    ) {
        $this->setObjectRepository($objectRepository);
        $this->setIdentityClassName($identityClassName);
        $this->setIdentityProperty($identityProperty);
        $this->setCredentialProperty($credentialProperty);
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate()
    {
        $this->authenticateSetup();
        $identity = $this->objectRepository->findOneBy(array($this->identityProperty => $this->identityValue));

        if (!$identity) {
            $this->authenticationResultInfo['code'] = AuthenticationResult::FAILURE_IDENTITY_NOT_FOUND;
            $this->authenticationResultInfo['messages'][] = 'A record with the supplied identity could not be found.';

            return $this->authenticateCreateAuthResult();
        }

        $authResult = $this->authenticateValidateIdentity($identity);

        return $authResult;
    }

    /**
     * Sets the object repository where to look for identities
     *
     * @param  ObjectRepository $objectRepository
     * @return self
     */
    public function setObjectRepository(ObjectRepository $objectRepository)
    {
        $this->objectRepository = $objectRepository;

        return $this;
    }

    /**
     * Sets the identity class to use for authentication.
     *
     * @param  string                                                          $identityClassName
     * @throws \Zend\Authentication\Adapter\Exception\InvalidArgumentException
     * @return self
     */
    public function setIdentityClassName($identityClassName)
    {
        if (!class_exists($identityClassName)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Provided $identityClassName "%s" does not exist or could not be loaded',
                $identityClassName
            ));
        }

        $this->identityClassName = $identityClassName;

        return $this;
    }

    /**
     * Set the value to be used as the identity
     *
     * @param  mixed $identityValue
     * @return self
     */
    public function setIdentityValue($identityValue)
    {
        $this->identityValue = $identityValue;

        return $this;
    }

    /**
     * Set the credential value to be used.
     *
     * @param  mixed $credentialValue
     * @return self
     */
    public function setCredentialValue($credentialValue)
    {
        $this->credentialValue = $credentialValue;

        return $this;
    }

    /**
     * Set the credential callable to be used to transform the password
     * before checking.
     *
     * @param  string                                                          $callable
     * @throws \Zend\Authentication\Adapter\Exception\InvalidArgumentException
     * @return self
     */
    public function setCredentialCallable($callable)
    {
        if (!is_callable($callable)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '"%s" is not a callable',
                is_string($callable) ? $callable : gettype($callable)
            ));
        }

        $this->credentialCallable = $callable;

        return $this;
    }

    /**
     * Set the identity callable to be used when saving the identity property to the
     * authentication result info.
     *
     * @param  string                                                          $callable
     * @throws \Zend\Authentication\Adapter\Exception\InvalidArgumentException
     * @return self
     */
    public function setIdentityCallable($callable)
    {
        if (!is_callable($callable)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '"%s" is not a callable',
                is_string($callable) ? $callable : gettype($callable)
            ));
        }

        $this->identityCallable = $callable;

        return $this;
    }

    /**
     * Set the property name to be used as the identity property
     *
     * @param  string                                                          $identityProperty
     * @throws \Zend\Authentication\Adapter\Exception\InvalidArgumentException
     * @return self
     */
    public function setIdentityProperty($identityProperty)
    {
        if (!$identityProperty) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Provided $identityProperty is invalid, %s given',
                gettype($identityProperty)
            ));
        }

        $this->identityProperty = (string) $identityProperty;

        return $this;
    }

    /**
     * Set the property name to be used as the credential property
     *
     * @param  string                                                          $credentialProperty
     * @throws \Zend\Authentication\Adapter\Exception\InvalidArgumentException
     * @return self
     */
    public function setCredentialProperty($credentialProperty)
    {
        if (!$credentialProperty) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Provided $credentialProperty is invalid, %s given',
                gettype($credentialProperty)
            ));
        }

        $this->credentialProperty = (string) $credentialProperty;

        return $this;
    }

    /**
     * This method attempts to validate that the record in the resultset is indeed a
     * record that matched the identity provided to this adapter.
     *
     * @param  object                                                          $identity
     * @throws \Zend\Authentication\Adapter\Exception\UnexpectedValueException
     * @return AuthenticationResult
     */
    protected function authenticateValidateIdentity($identity)
    {
        if (!$identity instanceof $this->identityClassName) {
            throw new Exception\UnexpectedValueException(sprintf(
                'Identity class type expected was %s, but got %s',
                $this->identityClassName,
                get_class($identity)
            ));
        }

        $getter = 'get' . ucfirst($this->credentialProperty);
        $vars = get_object_vars($identity);
        $documentCredential = null;

        if (method_exists($identity, $getter)) {
            $documentCredential = $identity->$getter();
        } elseif (isset($identity->{$this->credentialProperty}) || isset($vars[$this->credentialProperty])) {
            $documentCredential = $identity->{$this->credentialProperty};
        } else {
            throw new Exception\UnexpectedValueException(sprintf(
                'Property (%s) in (%s) is not accessible. You should implement %s::%s()',
                $this->credentialProperty,
                get_class($identity),
                get_class($identity),
                $getter
            ));
        }

        $credentialValue = $this->credentialValue;
        $callable = $this->credentialCallable;

        if ($callable) {
            $credentialValue = call_user_func($callable, $identity, $credentialValue);
        }

        if ($credentialValue !== true && $credentialValue != $documentCredential) {
            $this->authenticationResultInfo['code'] = AuthenticationResult::FAILURE_CREDENTIAL_INVALID;
            $this->authenticationResultInfo['messages'][] = 'Supplied credential is invalid.';

            return $this->authenticateCreateAuthResult();
        }

        $identityCallable = $this->identityCallable;
        if ($identityCallable) {
            $identity = call_user_func($identityCallable, $identity);
        }

        $this->authenticationResultInfo['code'] = AuthenticationResult::SUCCESS;
        $this->authenticationResultInfo['identity'] = $identity;
        $this->authenticationResultInfo['messages'][] = 'Authentication successful.';

        return $this->authenticateCreateAuthResult();
    }

    /**
     * This method abstracts the steps involved with making sure that this adapter was
     * indeed setup properly with all required pieces of information.
     *
     * @throws \Zend\Authentication\Adapter\Exception\RuntimeException - in the event that setup was not done properly
     * @return bool
     */
    protected function authenticateSetup()
    {
        if (null === $this->identityValue) {
            throw new Exception\RuntimeException(
                'A value for the identity was not provided prior to authentication with DoctrineObject authentication '
                    . 'adapter'
            );
        }

        if (null === $this->credentialValue) {
            throw new Exception\RuntimeException(
                'A credential value was not provided prior to authentication with DoctrineObject authentication adapter'
            );
        }

        $this->authenticationResultInfo = array(
            'code' => AuthenticationResult::FAILURE,
            'identity' => $this->identityValue,
            'messages' => array()
        );

        return true;
    }

    /**
     * Creates a Zend_Auth_Result object from the information that has been collected
     * during the authenticate() attempt.
     *
     * @return \Zend\Authentication\Result
     */
    protected function authenticateCreateAuthResult()
    {
        return new AuthenticationResult(
            $this->authenticationResultInfo['code'],
            $this->authenticationResultInfo['identity'],
            $this->authenticationResultInfo['messages']
        );
    }
}
