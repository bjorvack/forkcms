<?php

namespace Frontend\Modules\Mailmotor\Domain\Subscription\Validator\Constraints;

use MailMotor\Bundle\MailMotorBundle\Exception\NotImplementedException;
use MailMotor\Bundle\MailMotorBundle\Helper\Subscriber;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @Annotation
 */
class EmailSubscriptionValidator extends ConstraintValidator
{
    /**
     * @var Subscriber
     */
    protected $subscriber;

    /**
     * Set subscriber
     * Note: it's not possible by using a constructor
     *
     * @param Subscriber $subscriber
     */
    public function setSubscriber(Subscriber $subscriber): void
    {
        $this->subscriber = $subscriber;
    }

    /**
     * @param mixed $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        // There are already violations thrown, so we return immediately
        if (count($this->context->getViolations()) > 0) {
            return;
        }

        try {
            // The email is already in our mailing list
            if ($this->subscriber->isSubscribed($value)) {
                $this->context->buildViolation($constraint->alreadySubscribedMessage)->addViolation();
            }
            // fallback for when no mail-engine is chosen in the Backend
        } catch (NotImplementedException $e) {
            // do nothing
        }
    }
}
