<?php
namespace In2code\In2publishCore\Config\Validator;

use In2code\In2publishCore\Config\ValidationContainer;

class NotBlankValidator implements ValidatorInterface
{
    /**
     * @param ValidationContainer $container
     * @param mixed $value
     */
    public function validate(ValidationContainer $container, $value)
    {
        if ('' === $value || null === $value) {
            $container->addError('Configuration value must not be empty');
        }
    }
}
